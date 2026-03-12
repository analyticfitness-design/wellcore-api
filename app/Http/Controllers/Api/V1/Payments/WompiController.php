<?php

namespace App\Http\Controllers\Api\V1\Payments;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WompiController extends Controller
{
    /**
     * POST /api/v1/payments/create-session
     * Creates a Wompi payment link for plan upgrade/subscription.
     */
    public function createSession(Request $request): JsonResponse
    {
        $request->validate(['plan' => 'required|in:esencial,metodo,elite']);

        $prices = [
            'esencial' => 29900000, // $299.000 COP in cents
            'metodo'   => 39900000, // $399.000 COP
            'elite'    => 54900000, // $549.000 COP
        ];

        $plan      = $request->input('plan');
        $user      = $request->user();
        $amount    = $prices[$plan];
        $reference = 'WC-' . strtoupper($plan) . '-' . $user->id . '-' . time();

        // Wompi payment link generation
        $publicKey = config('services.wompi.public_key');
        $currency  = 'COP';

        $checkoutUrl = 'https://checkout.wompi.co/p/?' . http_build_query([
            'public-key'      => $publicKey,
            'currency'        => $currency,
            'amount-in-cents' => $amount,
            'reference'       => $reference,
            'customer-data:email'      => $user->email,
            'customer-data:full-name'  => $user->name,
            'redirect-url'   => config('app.url') . '/payment-success',
        ]);

        // Store pending payment
        Payment::create([
            'user_id'         => $user->id,
            'amount_cents'    => $amount,
            'currency'        => $currency,
            'status'          => 'PENDING',
            'wompi_reference' => $reference,
        ]);

        return response()->json([
            'checkout_url' => $checkoutUrl,
            'reference'    => $reference,
        ]);
    }

    public function webhook(Request $request): JsonResponse
    {
        // Verificar firma de integridad de Wompi ANTES de procesar
        if (! $this->verifyWompiSignature($request)) {
            Log::warning('Wompi webhook: firma inválida', [
                'ip' => $request->ip(),
            ]);
            return response()->json(['ok' => false, 'error' => 'Invalid signature'], 400);
        }

        $event       = $request->input('event');
        $transaction = $request->input('data.transaction');

        if ($event !== 'transaction.updated') {
            return response()->json(['ok' => true]);
        }

        $status = $transaction['status'] ?? '';

        $reference = $transaction['reference'] ?? '';

        // Find the pending payment by reference
        $payment = Payment::where('wompi_reference', $reference)->first();

        if ($status === 'APPROVED') {
            $user = $payment ? User::find($payment->user_id) : null;

            if (!$user) {
                // Fallback: find by email
                $email = $transaction['customer_data']['email'] ?? null;
                $user  = $email ? User::where('email', $email)->first() : null;
            }

            if ($user) {
                // Detect plan from reference (WC-METODO-123-timestamp)
                $plan = $this->extractPlanFromReference($reference);

                // Update user status and plan
                $user->update(['status' => 'activo']);
                if ($plan) {
                    $user->profile()?->update([
                        'plan' => $plan,
                        'subscription_expires_at' => now()->addDays(30),
                    ]);
                }

                // Update existing payment or create new
                if ($payment) {
                    $payment->update([
                        'status'          => 'APPROVED',
                        'wompi_tx_id'     => $transaction['id'] ?? null,
                        'payment_method'  => $transaction['payment_method_type'] ?? null,
                        'paid_at'         => now(),
                    ]);
                } else {
                    Payment::create([
                        'user_id'         => $user->id,
                        'amount_cents'    => $transaction['amount_in_cents'] ?? 0,
                        'currency'        => $transaction['currency'] ?? 'COP',
                        'status'          => 'APPROVED',
                        'wompi_reference' => $reference,
                        'wompi_tx_id'     => $transaction['id'] ?? null,
                        'payment_method'  => $transaction['payment_method_type'] ?? null,
                        'paid_at'         => now(),
                    ]);
                }

                // Send notification
                \App\Models\ClientNotification::send(
                    $user->id,
                    'payment',
                    '✅ Pago confirmado',
                    'Tu plan ' . strtoupper($plan ?? 'WellCore') . ' ha sido activado. ¡Bienvenido!',
                    ['plan' => $plan, 'reference' => $reference]
                );

                Log::info("Wompi payment approved for user {$user->id}, plan: {$plan}");
            }
        } elseif (in_array($status, ['DECLINED', 'ERROR', 'VOIDED'])) {
            if ($payment) {
                $payment->update(['status' => $status]);
            }
            Log::info("Wompi payment {$status} for reference: {$reference}");
        }

        return response()->json(['ok' => true]);
    }

    /**
     * GET /api/v1/payments/history
     * Returns the payment history for the authenticated user.
     */
    public function history(Request $request): JsonResponse
    {
        $payments = Payment::where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get()
            ->map(fn ($p) => [
                'id'              => $p->id,
                'amount_cents'    => $p->amount_cents,
                'currency'        => $p->currency,
                'status'          => $p->status,
                'reference'       => $p->wompi_reference,
                'payment_method'  => $p->payment_method,
                'paid_at'         => $p->paid_at?->toISOString(),
                'created_at'      => $p->created_at->toISOString(),
            ]);

        return response()->json(['data' => $payments]);
    }

    /**
     * Extract plan type from Wompi reference (WC-ELITE-42-1234567890).
     */
    private function extractPlanFromReference(string $reference): ?string
    {
        $plans = ['esencial', 'metodo', 'elite'];
        $parts = explode('-', strtolower($reference));

        foreach ($parts as $part) {
            if (in_array($part, $plans)) {
                return $part;
            }
        }

        return null;
    }

    /**
     * Verifica la firma de integridad enviada por Wompi.
     *
     * Wompi construye el checksum concatenando:
     *   {transaction.id}{amount_in_cents}{currency}{status}{WOMPI_INTEGRITY_SECRET}
     * y aplicando SHA-256.
     *
     * Documentación: https://docs.wompi.co/docs/en/widget-checkout-colombia
     */
    private function verifyWompiSignature(Request $request): bool
    {
        $secret = config('services.wompi.integrity_secret');

        // Si no hay secret configurado (dev/staging sin keys reales), skip verification
        if (empty($secret)) {
            return true;
        }

        $transaction = $request->input('data.transaction', []);
        $checksum    = $request->input('data.transaction.signature.checksum');

        if (empty($checksum)) {
            return false;
        }

        $id          = $transaction['id']             ?? '';
        $amountCents = $transaction['amount_in_cents'] ?? '';
        $currency    = $transaction['currency']        ?? '';
        $status      = $transaction['status']          ?? '';

        $expected = hash('sha256', "{$id}{$amountCents}{$currency}{$status}{$secret}");

        return hash_equals($expected, $checksum);
    }
}
