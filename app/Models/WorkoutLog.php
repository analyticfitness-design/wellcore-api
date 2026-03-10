<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkoutLog extends Model
{
    protected $fillable = ['user_id', 'exercise_name', 'sets', 'total_sets', 'notes', 'logged_at'];

    protected $casts = ['sets' => 'array', 'logged_at' => 'date'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
