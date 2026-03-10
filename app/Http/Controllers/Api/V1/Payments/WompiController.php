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

        if ($status === 'APPROVED') {
            $email = $transaction['customer_data']['email'] ?? null;
            $user  = $email ? User::where('email', $email)->first() : null;

            if ($user) {
                $user->update(['status' => 'activo']);

                Payment::create([
                    'user_id'         => $user->id,
                    'amount_cents'    => $transaction['amount_in_cents'] ?? 0,
                    'currency'        => $transaction['currency'] ?? 'COP',
                    'status'          => 'APPROVED',
                    'wompi_reference' => $transaction['reference'] ?? null,
                ]);

                Log::info("Wompi payment approved for user {$user->id}");
            }
        }

        return response()->json(['ok' => true]);
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
