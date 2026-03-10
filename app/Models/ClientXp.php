<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientXp extends Model
{
    use HasFactory;

    protected $table = 'client_xp';

    protected $fillable = [
        'user_id', 'xp_total', 'level', 'streak_days',
        'streak_protected', 'last_activity_date',
    ];

    protected $casts = [
        'last_activity_date' => 'date',
        'streak_protected' => 'boolean',
        'xp_total' => 'integer',
        'level' => 'integer',
        'streak_days' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
