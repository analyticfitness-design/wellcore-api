<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonalRecord extends Model
{
    protected $fillable = ['user_id', 'exercise_name', 'weight', 'reps', 'achieved_at', 'notes'];

    protected $casts = [
        'achieved_at' => 'date',
        'weight' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
