<?php

namespace App\Services;

use App\Models\Checkin;
use App\Models\ClientXp;
use App\Models\User;
use Illuminate\Support\Collection;

class CoachAnalyticsService
{
    public static function getDashboard(User $coach): array
    {
        $clients = User::where('coach_id', $coach->id)
            ->where('role', 'client')
            ->get();
        $clientIds = $clients->pluck('id');

        return [
            'overview' => [
                'total_clients'       => $clients->count(),
                'active_clients'      => $clients->where('status', 'activo')->count(),
                'avg_adherence_pct'   => self::calculateAvgAdherence($clientIds),
                'clients_with_streak' => ClientXp::whereIn('user_id', $clientIds)
                    ->where('streak_days', '>', 0)->count(),
            ],
            'clients_at_risk'  => self::getAtRiskClients($clients),
            'top_performers'   => self::getTopPerformers($clients),
            'weekly_trends'    => self::getWeeklyTrends($clientIds),
        ];
    }

    private static function calculateAvgAdherence(Collection $clientIds): float
    {
        if ($clientIds->isEmpty()) return 0;

        $totalCheckins = Checkin::whereIn('user_id', $clientIds)
            ->where('checkin_date', '>=', now()->subWeeks(4)->toDateString())
            ->count();

        $expected = $clientIds->count() * 4; // 1 por semana × 4 semanas
        return $expected > 0 ? round(($totalCheckins / $expected) * 100) : 0;
    }

    private static function getAtRiskClients(Collection $clients): Collection
    {
        return $clients->filter(function ($client) {
            $lastCheckin = Checkin::where('user_id', $client->id)
                ->orderByDesc('checkin_date')
                ->first();

            $daysSince = $lastCheckin
                ? (int) today()->diffInDays($lastCheckin->checkin_date)
                : 999;

            $xp = ClientXp::where('user_id', $client->id)->first();
            $streakBroken = $xp && $xp->streak_days === 0;

            return $streakBroken || $daysSince >= 14;
        })->map(fn ($c) => [
            'id'            => $c->id,
            'name'          => $c->name,
            'days_inactive' => (int) today()->diffInDays(
                Checkin::where('user_id', $c->id)->orderByDesc('checkin_date')->first()?->checkin_date
                ?? $c->created_at
            ),
            'risk_level'    => 'high',
        ])->values();
    }

    private static function getTopPerformers(Collection $clients): Collection
    {
        return $clients->sortByDesc(function ($client) {
            return ClientXp::where('user_id', $client->id)->first()?->xp_total ?? 0;
        })->take(5)->map(fn ($c) => [
            'id'          => $c->id,
            'name'        => $c->name,
            'total_xp'    => ClientXp::where('user_id', $c->id)->first()?->xp_total ?? 0,
            'streak_days' => ClientXp::where('user_id', $c->id)->first()?->streak_days ?? 0,
        ])->values();
    }

    private static function getWeeklyTrends(Collection $clientIds): array
    {
        $trends = [];
        for ($week = 3; $week >= 0; $week--) {
            $start = now()->subWeeks($week + 1)->startOfWeek();
            $end   = now()->subWeeks($week)->endOfWeek();

            $trends[] = [
                'week'     => $start->format('M d'),
                'checkins' => Checkin::whereIn('user_id', $clientIds)
                    ->whereBetween('checkin_date', [$start->toDateString(), $end->toDateString()])
                    ->count(),
            ];
        }
        return $trends;
    }
}
