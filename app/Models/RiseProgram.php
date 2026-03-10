<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiseProgram extends Model
{
    protected $fillable = [
        'user_id', 'start_date', 'end_date', 'duration_days',
        'experience_level', 'training_location', 'gender', 'intake_data', 'status',
    ];

    protected $casts = [
        'start_date'  => 'date',
        'end_date'    => 'date',
        'intake_data' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && today()->lte($this->end_date);
    }
}
