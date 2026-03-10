<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Metric extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'log_date', 'peso', 'porcentaje_grasa', 'porcentaje_musculo',
        'pecho', 'cintura', 'cadera', 'muslo', 'brazo', 'notas',
    ];

    protected $casts = [
        'log_date' => 'date',
        'peso' => 'float',
        'porcentaje_grasa' => 'float',
        'porcentaje_musculo' => 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
