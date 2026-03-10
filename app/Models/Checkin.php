<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Checkin extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'week', 'checkin_date', 'bienestar',
        'dias_entrenados', 'nutricion', 'comentario',
        'coach_reply', 'replied_at',
    ];

    protected $casts = [
        'checkin_date' => 'date',
        'replied_at' => 'datetime',
        'bienestar' => 'integer',
        'dias_entrenados' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
