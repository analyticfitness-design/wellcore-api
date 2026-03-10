<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'role', 'plan',
        'status', 'client_code', 'coach_id', 'fecha_inicio', 'birth_date',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'fecha_inicio' => 'date',
        'birth_date' => 'date',
    ];

    // Relationships
    public function profile(): HasOne
    {
        return $this->hasOne(ClientProfile::class);
    }

    public function xp(): HasOne
    {
        return $this->hasOne(ClientXp::class);
    }

    public function xpEvents(): HasMany
    {
        return $this->hasMany(XpEvent::class);
    }

    public function metrics(): HasMany
    {
        return $this->hasMany(Metric::class);
    }

    public function checkins(): HasMany
    {
        return $this->hasMany(Checkin::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(Photo::class);
    }

    public function coach(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coach_id');
    }

    public function clients(): HasMany
    {
        return $this->hasMany(User::class, 'coach_id');
    }

    public function coachNotes(): HasMany
    {
        return $this->hasMany(CoachNote::class, 'user_id');
    }

    public function coachMessages(): HasMany
    {
        return $this->hasMany(CoachMessage::class, 'client_id');
    }

    public function communityPosts(): HasMany
    {
        return $this->hasMany(CommunityPost::class);
    }

    public function nutritionLogs(): HasMany
    {
        return $this->hasMany(NutritionLog::class);
    }

    public function wellnessLogs(): HasMany
    {
        return $this->hasMany(WellnessLog::class);
    }

    public function biometricLogs(): HasMany
    {
        return $this->hasMany(BiometricLog::class);
    }

    // Role helpers
    public function isCoach(): bool
    {
        return $this->role === 'coach';
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['admin', 'superadmin']);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'superadmin';
    }

    public function isClient(): bool
    {
        return $this->role === 'client';
    }

    // Plan hierarchy check (esencial=1, metodo=2, elite=3, rise=1)
    public function hasPlan(string $minPlan): bool
    {
        $hierarchy = ['esencial' => 1, 'metodo' => 2, 'elite' => 3, 'rise' => 1];
        return ($hierarchy[$this->plan] ?? 0) >= ($hierarchy[$minPlan] ?? 0);
    }
}
