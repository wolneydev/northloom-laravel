<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startsAt = fake()->dateTimeBetween('-1 week', '+1 week');
        $endsAt = (clone $startsAt)->modify('+2 hours');

        return [
            'user_id' => User::factory(),
            'project_id' => Project::factory(),
            'title' => fake()->sentence(4),
            'task_date' => $startsAt->format('Y-m-d'),
            'starts_at' => $startsAt->format('Y-m-d H:i:s'),
            'ends_at' => $endsAt->format('Y-m-d H:i:s'),
            'notes' => fake()->optional()->paragraph(),
            'location' => fake()->optional()->city(),
            'priority' => fake()->randomElement(['low', 'medium', 'high']),
            'status' => 'pending',
            'notify' => false,
            'notify_minutes_before' => null,
        ];
    }

    /**
     * Anchor the task to an existing project and reuse its owner.
     */
    public function forProject(Project $project): static
    {
        return $this->state(fn (array $attributes): array => [
            'project_id' => $project->id,
            'user_id' => $project->user_id,
        ]);
    }
}
