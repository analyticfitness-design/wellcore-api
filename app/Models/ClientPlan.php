<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientPlan extends Model
{
    protected $fillable = ['user_id', 'coach_id', 'entrenamiento', 'nutricion', 'habitos', 'suplementacion', 'ciclo', 'bloodwork', 'version'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function coach(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coach_id');
    }
}
