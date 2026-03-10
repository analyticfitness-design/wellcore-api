<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PodMember extends Model
{
    public $timestamps = false;

    protected $fillable = ['pod_id', 'user_id', 'joined_at'];

    protected $casts = ['joined_at' => 'datetime'];

    public function pod(): BelongsTo
    {
        return $this->belongsTo(Pod::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
