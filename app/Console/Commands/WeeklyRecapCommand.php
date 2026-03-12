<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WeeklyRecapCommand extends Command
{
    protected $signature = 'wellcore:weekly-recap';
    protected $description = 'Envía resumen semanal de actividad a cada cliente activo';

    public function handle(): int
    {
        $this->info('[' . now() . '] Generando recaps semanales...');

        $weekStart = now()->subWeek()->startOfWeek();
        $weekEnd = now()->subWeek()->endOfWeek();

        // Active users with at least one activity this week
        $users = DB::table('users as u')
            ->join('client_xp as cx', 'cx.user_id', '=', 'u.id')
            ->where('u.active', 1)
            ->where('u.role', 'client')
            ->select('u.id', 'u.name', 'cx.total_xp', 'cx.level', 'cx.streak_days')
            ->limit(200)
            ->get();

        $count = 0;
        foreach ($users as $user) {
            $checkins = DB::table('checkins')
                ->where('user_id', $user->id)
                ->whereBetween('created_at', [$weekStart, $weekEnd])
                ->count();

            $workouts = DB::table('workout_logs')
                ->where('user_id', $user->id)
                ->whereBetween('created_at', [$weekStart, $weekEnd])
                ->count();

            // Only send if they had any activity
            if ($checkins + $workouts === 0) continue;

            $xpEarned = DB::table('xp_transactions')
                ->where('user_id', $user->id)
                ->whereBetween('created_at', [$weekStart, $weekEnd])
                ->sum('amount');

            \App\Models\ClientNotification::send(
                $user->id,
                'xp_earned',
                '📊 Tu semana en WellCore',
                "Check-ins: {$checkins} | Entrenamientos: {$workouts} | XP ganado: {$xpEarned} | Racha: {$user->streak_days} días",
                [
                    'checkins' => $checkins,
                    'workouts' => $workouts,
                    'xp_earned' => (int) $xpEarned,
                    'streak' => $user->streak_days,
                ]
            );
            $count++;
        }

        $this->info("Recaps enviados: {$count}");
        Log::info('WeeklyRecap completado', ['sent' => $count]);

        return Command::SUCCESS;
    }
}
