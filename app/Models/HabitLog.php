<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HabitLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'log_date',
        'habit_type',
        'value',
    ];

    protected $casts = [
        'log_date' => 'date',
        'value' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Calculate streak: consecutive days where all 4 habits are completed.
     */
    public static function calculateStreak(int $userId): int
    {
        $today = now()->toDateString();
        $streak = 0;
        $date = now();

        while (true) {
            $dateStr = $date->toDateString();
            $completedCount = self::where('user_id', $userId)
                ->where('log_date', $dateStr)
                ->where('value', 1)
                ->count();

            if ($completedCount >= 4) {
                $streak++;
                $date = $date->subDay();
            } else {
                // If today and not all done yet, check yesterday
                if ($dateStr === $today && $streak === 0) {
                    $date = $date->subDay();
                    continue;
                }
                break;
            }

            // Safety limit
            if ($streak > 365) break;
        }

        return $streak;
    }
}
