<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WellnessLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'log_date', 'energy_level', 'stress_level',
        'sleep_hours', 'sleep_quality', 'mood', 'notes',
    ];

    protected $casts = [
        'log_date' => 'date',
        'sleep_hours' => 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
