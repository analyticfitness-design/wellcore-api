<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NutritionLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'log_date', 'calories_target', 'calories_actual',
        'protein_g', 'carbs_g', 'fat_g', 'adherence_pct', 'meal_photo_url', 'notes',
    ];

    protected $casts = [
        'log_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
