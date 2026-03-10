<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Checkin>
 */
class CheckinFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'week' => fake()->numberBetween(1, 52),
            'checkin_date' => fake()->dateTimeBetween('-30 days', 'now')->format('Y-m-d'),
            'bienestar' => fake()->numberBetween(1, 10),
            'dias_entrenados' => fake()->numberBetween(0, 7),
            'nutricion' => fake()->randomElement(['Si', 'No', 'Parcial']),
            'comentario' => fake()->sentence(),
        ];
    }
}
