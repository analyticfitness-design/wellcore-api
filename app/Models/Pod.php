<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pod extends Model
{
    use HasFactory;

    protected $fillable = ['coach_id', 'name', 'description', 'privacy', 'max_members'];

    public function coach(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coach_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(PodMember::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'pod_members', 'pod_id', 'user_id');
    }
}
