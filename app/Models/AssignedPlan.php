<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssignedPlan extends Model
{
    protected $fillable = ['user_id', 'plan_type', 'content', 'version', 'active', 'valid_from'];

    protected $casts = [
        'active'     => 'boolean',
        'valid_from' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
