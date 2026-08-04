<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startsOn = fake()->dateTimeBetween('-1 week', '+1 week');

        return [
            'user_id' => User::factory(),
            'name' => fake()->sentence(3),
            'currency' => 'BRL',
            'starts_on' => $startsOn->format('Y-m-d'),
            'expected_ends_on' => fake()->dateTimeBetween($startsOn, '+2 months')->format('Y-m-d'),
            'notes' => fake()->optional()->paragraph(),
        ];
    }
}
