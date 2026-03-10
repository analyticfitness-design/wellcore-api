<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class B2bSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'coach_id', 'plan', 'max_clients', 'monthly_price', 'billing_date', 'status',
    ];

    protected $casts = ['billing_date' => 'date'];

    public function coach(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coach_id');
    }
}
