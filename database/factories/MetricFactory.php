<?php

namespace Database\Factories;

use App\Models\Metric;
use Illuminate\Database\Eloquent\Factories\Factory;

class MetricFactory extends Factory
{
    protected $model = Metric::class;

    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'log_date' => fake()->unique()->dateTimeThisYear()->format('Y-m-d'),
            'peso' => fake()->randomFloat(1, 50, 120),
            'porcentaje_grasa' => fake()->randomFloat(1, 8, 35),
            'porcentaje_musculo' => fake()->randomFloat(1, 30, 55),
        ];
    }
}
