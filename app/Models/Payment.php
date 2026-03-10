<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'user_id', 'amount_cents', 'currency', 'status',
        'wompi_reference', 'payment_method', 'metadata',
    ];

    protected $casts = ['metadata' => 'array'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getAmountCopAttribute(): string
    {
        return '$' . number_format($this->amount_cents / 100, 0, ',', '.');
    }
}
