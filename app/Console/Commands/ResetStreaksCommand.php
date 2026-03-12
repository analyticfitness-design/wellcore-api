<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ResetStreaksCommand extends Command
{
    protected $signature = 'wellcore:reset-streaks';
    protected $description = 'Resetea rachas de usuarios sin actividad en las últimas 48h';

    public function handle(): int
    {
        $this->info('[' . now() . '] Iniciando reset de rachas...');

        // Users with streak > 0 but no activity in 48h → reset streak to 0
        $reset = DB::table('client_xp')
            ->where('streak_days', '>', 0)
            ->where('last_activity_date', '<', now()->subHours(48))
            ->update(['streak_days' => 0]);

        $this->info("Rachas reseteadas: {$reset}");
        Log::info('ResetStreaks completado', ['reset_count' => $reset]);

        return Command::SUCCESS;
    }
}
