<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BiometricLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'log_date', 'steps', 'sleep_hours', 'heart_rate',
        'weight_kg', 'body_fat_pct', 'energy_level', 'source',
    ];

    protected $casts = [
        'log_date'     => 'date',
        'sleep_hours'  => 'float',
        'weight_kg'    => 'float',
        'body_fat_pct' => 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
