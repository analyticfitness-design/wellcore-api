<?php

namespace App\Services;

use App\Models\ClientXp;
use App\Models\User;
use App\Models\XpEvent;
use Illuminate\Support\Facades\Cache;

class GamificationService
{
    public const XP_MAP = [
        'checkin'        => 10,
        'video_checkin'  => 20,
        'streak_7'       => 50,
        'streak_30'      => 150,
        'badge'          => 30,
        'challenge'      => 100,
        'referral'       => 75,
        'bonus'          => 0,
        'nutrition_log'  => 5,
        'wellness_log'   => 5,
        'metric_log'     => 5,
        'photo_upload'   => 15,
    ];

    public const LEVEL_NAMES = [
        1  => 'Iniciado',
        2  => 'Aprendiz',
        3  => 'Atleta',
        4  => 'Guerrero',
        5  => 'Élite',
        6  => 'Campeón',
        7  => 'Leyenda',
        8  => 'WellCore Master',
    ];

    public const LEVEL_XP = [
        1 => 0,
        2 => 200,
        3 => 500,
        4 => 1000,
        5 => 2000,
        6 => 4000,
        7 => 7500,
        8 => 12000,
    ];

    public function earnXp(User $user, string $eventType, ?int $customAmount = null): ClientXp
    {
        $amount = $customAmount ?? (self::XP_MAP[$eventType] ?? 0);
        if ($amount <= 0) {
            return $this->getOrCreateXp($user);
        }

        XpEvent::create([
            'user_id'    => $user->id,
            'event_type' => $eventType,
            'xp_gained'  => $amount,
            'description' => "XP gained: {$eventType}",
        ]);

        $xp = $this->getOrCreateXp($user);
        $xp->increment('xp_total', $amount);
        $xp->refresh();

        $newLevel = $this->calculateLevel($xp->xp_total);
        if ($newLevel !== $xp->level) {
            $xp->update(['level' => $newLevel]);
        }

        $coachId = $user->coach_id ?? $user->id;
        Cache::forget("leaderboard.{$coachId}.week");

        return $xp->fresh();
    }

    public function updateStreak(User $user): void
    {
        $xp = $this->getOrCreateXp($user);
        $today = now()->toDateString();
        $lastActivity = $xp->last_activity_date?->toDateString();

        if ($lastActivity === $today) {
            return;
        }

        $yesterday = now()->subDay()->toDateString();
        if ($lastActivity === $yesterday) {
            $newStreak = $xp->streak_days + 1;
        } else {
            $newStreak = 1;
        }

        $xp->update([
            'streak_days'        => $newStreak,
            'last_activity_date' => $today,
        ]);

        if ($newStreak === 7) {
            $this->earnXp($user, 'streak_7');
        } elseif ($newStreak === 30) {
            $this->earnXp($user, 'streak_30');
        }
    }

    public function getStats(User $user): array
    {
        $xp = $this->getOrCreateXp($user);
        $level = $xp->level;
        $levelName = self::LEVEL_NAMES[$level] ?? 'WellCore Master';
        $nextLevelXp = self::LEVEL_XP[$level + 1] ?? self::LEVEL_XP[8];
        $currentLevelXp = self::LEVEL_XP[$level] ?? 0;
        $progressPct = $nextLevelXp > $currentLevelXp
            ? (int) (($xp->xp_total - $currentLevelXp) / ($nextLevelXp - $currentLevelXp) * 100)
            : 100;

        return [
            'xp_total'        => $xp->xp_total,
            'level'           => $level,
            'level_name'      => $levelName,
            'streak_days'     => $xp->streak_days,
            'xp_next_level'   => $nextLevelXp,
            'xp_progress_pct' => min(100, max(0, $progressPct)),
            'recent_events'   => XpEvent::where('user_id', $user->id)
                ->orderByDesc('created_at')
                ->limit(5)
                ->get(['event_type', 'xp_gained', 'description', 'created_at']),
        ];
    }

    public function getAchievements(User $user): array
    {
        $xp = $this->getOrCreateXp($user);
        $events = XpEvent::where('user_id', $user->id)
            ->pluck('event_type')
            ->toArray();

        $checkinCount = $user->checkins()->count();
        $photoCount   = $user->photos()->count();

        return [
            ['id' => 'first_checkin',  'name' => 'Primer Check-in',    'emoji' => '✅', 'xp' => 10,  'unlocked' => in_array('checkin', $events)],
            ['id' => 'streak_7',       'name' => 'Racha de 7 días',    'emoji' => '🔥', 'xp' => 50,  'unlocked' => $xp->streak_days >= 7],
            ['id' => 'streak_30',      'name' => 'Racha de 30 días',   'emoji' => '💎', 'xp' => 150, 'unlocked' => $xp->streak_days >= 30],
            ['id' => 'checkins_10',    'name' => '10 Check-ins',       'emoji' => '💪', 'xp' => 100, 'unlocked' => $checkinCount >= 10],
            ['id' => 'checkins_30',    'name' => '30 Check-ins',       'emoji' => '🏆', 'xp' => 300, 'unlocked' => $checkinCount >= 30],
            ['id' => 'photo_progress', 'name' => 'Foto de progreso',   'emoji' => '📸', 'xp' => 15,  'unlocked' => $photoCount >= 1],
            ['id' => 'level_3',        'name' => 'Nivel Atleta',       'emoji' => '⚡', 'xp' => 0,   'unlocked' => $xp->level >= 3],
            ['id' => 'level_5',        'name' => 'Nivel Élite',        'emoji' => '👑', 'xp' => 0,   'unlocked' => $xp->level >= 5],
            ['id' => 'challenge',      'name' => 'Primer Reto',        'emoji' => '🎯', 'xp' => 100, 'unlocked' => in_array('challenge', $events)],
            ['id' => 'referral',       'name' => 'Embajador WellCore', 'emoji' => '🌟', 'xp' => 75,  'unlocked' => in_array('referral', $events)],
        ];
    }

    private function getOrCreateXp(User $user): ClientXp
    {
        return ClientXp::firstOrCreate(
            ['user_id' => $user->id],
            ['xp_total' => 0, 'level' => 1, 'streak_days' => 0]
        );
    }

    private function calculateLevel(int $xpTotal): int
    {
        $level = 1;
        foreach (self::LEVEL_XP as $lvl => $required) {
            if ($xpTotal >= $required) {
                $level = $lvl;
            }
        }
        return min($level, 8);
    }
}
