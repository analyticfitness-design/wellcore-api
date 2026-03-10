<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'edad', 'peso', 'altura', 'objetivo', 'ciudad',
        'whatsapp', 'nivel', 'lugar_entreno', 'dias_disponibles',
        'restricciones', 'macros', 'bio', 'avatar_url', 'dashboard_video_url',
    ];

    protected $casts = [
        'dias_disponibles' => 'array',
        'macros' => 'array',
        'peso' => 'float',
        'altura' => 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
