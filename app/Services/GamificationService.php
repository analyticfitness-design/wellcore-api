<?php

namespace App\Services;

use App\Events\LeaderboardUpdated;
use App\Models\ClientXp;
use App\Models\User;
use App\Models\XpEvent;
use Illuminate\Support\Facades\DB;

class GamificationService
{
    const LEVELS = [
        1 => 0, 2 => 200, 3 => 500, 4 => 1000, 5 => 2000, 6 => 4000,
    ];

    const XP_EVENTS = [
        'checkin'       => 50,
        'video_checkin' => 80,
        'challenge'     => 200,
        'badge'         => 100,
        'referral'      => 300,
    ];

    public static function earnXp(User $user, string $eventType, int $customAmount = 0): array
    {
        $amount = $customAmount ?: (self::XP_EVENTS[$eventType] ?? 0);
        $result = [];

        DB::transaction(function () use ($user, $eventType, $amount, &$result) {
            $xp = ClientXp::firstOrCreate(
                ['user_id' => $user->id],
                ['xp_total' => 0, 'level' => 1, 'streak_days' => 0]
            );

            $streakBonus = self::updateStreak($xp);
            $total = $amount + $streakBonus;

            $xp->increment('xp_total', $total);
            $freshXp = $xp->fresh();
            $freshXp->level = self::calculateLevel($freshXp->xp_total);
            $freshXp->save();

            XpEvent::create([
                'user_id'    => $user->id,
                'event_type' => $eventType,
                'xp_gained'  => $total,
                'description' => "Ganaste {$total} XP por {$eventType}",
            ]);

            $result = ['xp_gained' => $total, 'streak_bonus' => $streakBonus];
        });

        event(new LeaderboardUpdated());

        return array_merge($result, ['user_xp' => $user->fresh()->xp]);
    }

    private static function updateStreak(ClientXp $xp): int
    {
        $bonus = 0;
        $lastActivity = $xp->last_activity_date;

        if ($lastActivity === null) {
            $xp->streak_days = 1;
        } elseif ($lastActivity->isYesterday()) {
            $xp->streak_days += 1;
        } elseif (!$lastActivity->isToday()) {
            $xp->streak_days = 1;
        }

        $xp->last_activity_date = today();
        $xp->save();

        if ($xp->streak_days === 7) {
            $bonus = 150;
        } elseif ($xp->streak_days === 30) {
            $bonus = 500;
        }

        return $bonus;
    }

    private static function calculateLevel(int $xpTotal): int
    {
        $level = 1;
        foreach (self::LEVELS as $lvl => $threshold) {
            if ($xpTotal >= $threshold) {
                $level = $lvl;
            }
        }
        return $level;
    }

    public static function getStatus(User $user): array
    {
        $xp = $user->xp ?? ClientXp::firstOrCreate(['user_id' => $user->id]);
        $currentThreshold = self::LEVELS[$xp->level];
        $nextLevel = min($xp->level + 1, 6);
        $nextThreshold = self::LEVELS[$nextLevel];

        $progressPct = 100;
        if ($nextThreshold > $currentThreshold) {
            $progressPct = (int) round(
                ($xp->xp_total - $currentThreshold) / ($nextThreshold - $currentThreshold) * 100
            );
        }

        return [
            'xp_total'        => $xp->xp_total,
            'level'           => $xp->level,
            'level_name'      => self::levelName($xp->level),
            'xp_next_level'   => $nextThreshold,
            'xp_progress_pct' => $progressPct,
            'streak_days'     => $xp->streak_days,
            'streak_active'   => $xp->last_activity_date?->isToday() ?? false,
            'recent_events'   => XpEvent::where('user_id', $user->id)
                ->orderByDesc('created_at')->limit(5)->get(),
        ];
    }

    private static function levelName(int $level): string
    {
        return match ($level) {
            1 => 'Iniciado',
            2 => 'Comprometido',
            3 => 'Constante',
            4 => 'Dedicado',
            5 => 'Elite',
            6 => 'Leyenda',
            default => 'Iniciado'
        };
    }
}
