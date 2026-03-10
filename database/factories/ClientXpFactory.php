<?php

namespace Database\Factories;

use App\Models\ClientXp;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClientXpFactory extends Factory
{
    protected $model = ClientXp::class;

    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'xp_total' => fake()->numberBetween(0, 2000),
            'level' => fake()->numberBetween(1, 5),
            'streak_days' => fake()->numberBetween(0, 30),
            'streak_protected' => false,
            'last_activity_date' => fake()->dateTimeThisMonth(),
        ];
    }
}
