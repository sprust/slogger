<?php

namespace Database\Factories\Projects;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name'    => fake()->userName,
        ];
    }
}
