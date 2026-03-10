<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AutoRenewalCommand extends Command
{
    protected $signature = 'wellcore:auto-renewal';
    protected $description = 'Notifica a usuarios con suscripción próxima a vencer';

    public function handle(): int
    {
        $this->info('[' . now() . '] Iniciando auto-renewal check...');

        // Usuarios que vencen en 3 días
        $expiring = DB::table('users as u')
            ->join('client_profiles as cp', 'cp.user_id', '=', 'u.id')
            ->where('u.active', 1)
            ->whereBetween('cp.subscription_expires_at', [now(), now()->addDays(3)])
            ->select('u.id', 'u.name', 'cp.plan', 'cp.subscription_expires_at')
            ->limit(20)
            ->get();

        foreach ($expiring as $user) {
            \App\Models\ClientNotification::send(
                $user->id,
                'appointment',
                '🔔 Tu suscripción vence pronto',
                'Tu plan ' . $user->plan . ' vence el ' . date('d/m/Y', strtotime($user->subscription_expires_at)) . '. Renueva para continuar.',
                ['expires_at' => $user->subscription_expires_at, 'plan' => $user->plan]
            );
        }

        $this->info("Próximos a vencer: {$expiring->count()}");
        Log::info('AutoRenewal check completado', ['expiring' => $expiring->count()]);

        return Command::SUCCESS;
    }
}
