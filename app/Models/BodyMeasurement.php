<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BodyMeasurement extends Model
{
    protected $fillable = ['user_id', 'weight', 'waist', 'hip', 'chest', 'arm', 'thigh', 'body_fat', 'logged_at', 'notes'];

    protected $casts = ['logged_at' => 'date'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
