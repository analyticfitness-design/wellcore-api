<?php

namespace App\Jobs;

use App\Models\AutoMessageLog;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class SendBehavioralTrigger implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    private const TRIGGERS = [
        ['type' => 'inactive_7d',  'min_days' => 7,  'max_days' => 13],
        ['type' => 'inactive_14d', 'min_days' => 14, 'max_days' => 29],
        ['type' => 'welcome_day1', 'min_days' => 1,  'max_days' => 3, 'mode' => 'welcome'],
        ['type' => 'milestone_7',  'mode' => 'milestone', 'count' => 7],
        ['type' => 'milestone_30', 'mode' => 'milestone', 'count' => 30],
    ];

    public function handle(): void
    {
        foreach (self::TRIGGERS as $trigger) {
            $this->processTrigger($trigger);
        }
    }

    private function processTrigger(array $trigger): void
    {
        $mode = $trigger['mode'] ?? 'inactive';

        $candidates = match ($mode) {
            'inactive'  => $this->getInactiveCandidates($trigger['min_days'], $trigger['max_days']),
            'welcome'   => $this->getWelcomeCandidates($trigger['min_days'], $trigger['max_days']),
            'milestone' => $this->getMilestoneCandidates($trigger['count']),
            default     => collect(),
        };

        foreach ($candidates as $client) {
            if ($this->alreadySentToday($client->id, $trigger['type'])) {
                continue;
            }

            // En producción real: dispatch email/push aquí
            // SendTriggerEmail::dispatch($client, $trigger['type']);
            Log::info("Behavioral trigger: {$trigger['type']} → user {$client->id}");

            $this->logTriggerSent($client->id, $trigger['type'], 'email');
        }
    }

    public function getInactiveCandidates(int $minDays, int $maxDays): Collection
    {
        $from = now()->subDays($maxDays)->toDateString();
        $to   = now()->subDays($minDays)->toDateString();

        return User::where('role', 'client')
            ->where('status', 'activo')
            ->whereHas('checkins', function ($q) use ($from, $to) {
                $q->whereBetween('checkin_date', [$from, $to]);
            })
            ->whereDoesntHave('checkins', function ($q) {
                $q->where('checkin_date', '>=', now()->subDays(6)->toDateString());
            })
            ->get();
    }

    private function getWelcomeCandidates(int $minDays, int $maxDays): Collection
    {
        return User::where('role', 'client')
            ->where('status', 'activo')
            ->whereBetween('created_at', [
                now()->subDays($maxDays)->startOfDay(),
                now()->subDays($minDays)->endOfDay(),
            ])
            ->get();
    }

    private function getMilestoneCandidates(int $streakCount): Collection
    {
        return User::where('role', 'client')
            ->whereHas('xp', fn ($q) => $q->where('streak_days', $streakCount))
            ->get();
    }

    public function alreadySentToday(int $userId, string $triggerType): bool
    {
        return AutoMessageLog::where('user_id', $userId)
            ->where('trigger_type', $triggerType)
            ->whereDate('date_sent', today())
            ->exists();
    }

    public function logTriggerSent(int $userId, string $triggerType, string $channel = 'email'): void
    {
        AutoMessageLog::firstOrCreate([
            'user_id'      => $userId,
            'trigger_type' => $triggerType,
            'date_sent'    => today()->toDateString(),
        ], [
            'channel' => $channel,
        ]);
    }
}
