<?php

namespace Database\Factories;

use App\Models\Pod;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PodFactory extends Factory
{
    protected $model = Pod::class;

    public function definition(): array
    {
        return [
            'coach_id'    => User::factory()->create(['role' => 'coach'])->id,
            'name'        => fake()->words(3, true),
            'description' => fake()->sentence(),
            'privacy'     => 'private',
            'max_members' => 8,
        ];
    }
}
