<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BehavioralTriggersCommand extends Command
{
    protected $signature = 'wellcore:behavioral-triggers';
    protected $description = 'Evalúa triggers comportamentales y envía notificaciones automáticas';

    public function handle(): int
    {
        $this->info('[' . now() . '] Iniciando behavioral triggers...');

        // Usuarios con racha en riesgo (sin actividad hace 20-24h pero tienen racha >= 7 días)
        $streakRisk = DB::table('client_xp')
            ->where('streak_days', '>=', 7)
            ->where('last_activity_date', '<', now()->subHours(20))
            ->where('last_activity_date', '>=', now()->subHours(24))
            ->limit(50)
            ->get();

        foreach ($streakRisk as $xp) {
            \App\Models\ClientNotification::send(
                $xp->user_id,
                'streak',
                '⚡ ¡Tu racha está en peligro!',
                "Llevas {$xp->streak_days} días de racha. Haz check-in hoy para no perderla.",
                ['streak_days' => $xp->streak_days]
            );
        }

        $this->info("Rachas en riesgo notificadas: {$streakRisk->count()}");

        // Usuarios sin actividad hace exactamente 3 días
        $inactive = DB::table('client_xp')
            ->where('last_activity_date', '<', now()->subDays(3))
            ->where('last_activity_date', '>=', now()->subDays(4))
            ->limit(50)
            ->get();

        foreach ($inactive as $xp) {
            \App\Models\ClientNotification::send(
                $xp->user_id,
                'streak',
                '💪 ¡Te extrañamos!',
                '¿Todo bien? Hace 3 días que no registras actividad. ¡Vuelve hoy!',
                ['days_inactive' => 3]
            );
        }

        $this->info("Inactivos 3 días notificados: {$inactive->count()}");
        Log::info('BehavioralTriggers completado', [
            'streak_risk' => $streakRisk->count(),
            'inactive' => $inactive->count(),
        ]);

        return Command::SUCCESS;
    }
}
