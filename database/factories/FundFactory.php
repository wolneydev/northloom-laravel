<?php

namespace Database\Factories;

use App\Models\Fund;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Fund> */
class FundFactory extends Factory
{
    public function definition(): array
    {
        $balance = fake()->randomFloat(2, 0, 100000);

        return [
            'project_id' => Project::factory(),
            'name' => fake()->words(3, true),
            'opening_balance' => number_format($balance, 2, '.', ''),
            'available_balance' => number_format($balance, 2, '.', ''),
        ];
    }
}
