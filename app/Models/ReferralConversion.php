<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReferralConversion extends Model
{
    protected $fillable = ['referral_id', 'referred_user_id', 'status'];

    public function referral(): BelongsTo
    {
        return $this->belongsTo(Referral::class);
    }
}
