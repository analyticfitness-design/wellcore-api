<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CommunityPostFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'   => User::factory(),
            'content'   => $this->faker->sentence(10),
            'post_type' => $this->faker->randomElement(['text', 'workout', 'milestone']),
            'audience'  => 'all',
            'parent_id' => null,
        ];
    }
}
