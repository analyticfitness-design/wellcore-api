<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => 'client',
            'plan' => fake()->randomElement(['esencial', 'metodo', 'elite']),
            'status' => 'activo',
            'client_code' => 'client-' . strtoupper(fake()->lexify('??????')),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function coach(): static
    {
        return $this->state(['role' => 'coach', 'plan' => null, 'client_code' => null]);
    }

    public function admin(): static
    {
        return $this->state(['role' => 'admin', 'plan' => null, 'client_code' => null]);
    }

    public function client(): static
    {
        return $this->state(['role' => 'client']);
    }
}
