<?php

namespace Database\Factories;

use App\Models\FinancialAllocation;
use App\Models\Fund;
use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<FinancialAllocation> */
class FinancialAllocationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'task_id' => Task::factory(),
            'fund_id' => Fund::factory(),
            'amount' => number_format(fake()->randomFloat(2, 0.01, 1000), 2, '.', ''),
            'allocated_at' => now(),
        ];
    }
}
