<?php

namespace Database\Factories;

use App\Models\Cost;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Cost> */
class CostFactory extends Factory
{
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'amount' => number_format(fake()->randomFloat(2, 0.01, 10000), 2, '.', ''),
            'description' => fake()->sentence(),
            'incurred_on' => fake()->date(),
        ];
    }
}
