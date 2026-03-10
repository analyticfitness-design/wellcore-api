<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExerciseVideo extends Model
{
    protected $fillable = ['title', 'youtube_url', 'youtube_id', 'gender', 'category', 'muscle_group', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
