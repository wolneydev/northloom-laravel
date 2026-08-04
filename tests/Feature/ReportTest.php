<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_generate_task_report_with_status_and_date_filters(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        Task::factory()->forProject($project)->create([
            'title' => 'Outside period',
            'task_date' => '2026-06-10',
            'status' => 'completed',
        ]);

        Task::factory()->forProject($project)->create([
            'title' => 'Inside report',
            'task_date' => '2026-06-15',
            'starts_at' => '2026-06-15 09:00:00',
            'status' => 'completed',
        ]);

        Task::factory()->forProject($project)->create([
            'title' => 'Newest inside report',
            'task_date' => '2026-06-18',
            'starts_at' => '2026-06-18 09:00:00',
            'status' => 'completed',
        ]);

        Task::factory()->forProject($project)->create([
            'title' => 'Wrong status',
            'task_date' => '2026-06-15',
            'status' => 'pending',
        ]);

        Task::factory()->create([
            'title' => 'Another user task',
            'task_date' => '2026-06-15',
            'status' => 'completed',
        ]);

        Passport::actingAs($user);

        $response = $this->getJson('/api/reports?report_type=tasks&status=completed&start_date=2026-06-12&end_date=2026-06-20');

        $response->assertOk()
            ->assertJsonPath('data.filters.report_type', 'tasks')
            ->assertJsonCount(2, 'data.tasks')
            ->assertJsonPath('data.tasks.0.title', 'Newest inside report')
            ->assertJsonPath('data.tasks.1.title', 'Inside report')
            ->assertJsonMissingPath('data.projects');
    }

    public function test_user_can_generate_project_report_filtered_by_task_status(): void
    {
        $user = User::factory()->create();
        $projectWithCompletedTask = Project::factory()->create([
            'user_id' => $user->id,
            'name' => 'Project with completed task',
            'starts_on' => '2026-06-01',
            'expected_ends_on' => '2026-06-30',
        ]);
        $projectWithoutCompletedTask = Project::factory()->create([
            'user_id' => $user->id,
            'name' => 'Project without completed task',
            'starts_on' => '2026-06-01',
            'expected_ends_on' => '2026-06-30',
        ]);

        Task::factory()->forProject($projectWithCompletedTask)->create([
            'task_date' => '2026-06-15',
            'status' => 'completed',
        ]);
        Task::factory()->forProject($projectWithCompletedTask)->create([
            'task_date' => '2026-06-16',
            'status' => 'pending',
        ]);
        Task::factory()->forProject($projectWithoutCompletedTask)->create([
            'task_date' => '2026-06-15',
            'status' => 'pending',
        ]);

        Passport::actingAs($user);

        $response = $this->getJson('/api/reports?report_type=projects&status=completed&start_date=2026-06-01&end_date=2026-06-30');

        $response->assertOk()
            ->assertJsonCount(1, 'data.projects')
            ->assertJsonPath('data.projects.0.name', 'Project with completed task')
            ->assertJsonPath('data.projects.0.currency', 'BRL')
            ->assertJsonPath('data.projects.0.tasks_count', 1)
            ->assertJsonPath('data.projects.0.status_counts.completed', 1)
            ->assertJsonPath('data.projects.0.status_counts.pending', 1)
            ->assertJsonMissingPath('data.tasks');
    }

    public function test_user_can_generate_report_with_projects_and_tasks(): void
    {
        $user = User::factory()->create();
        $olderProject = Project::factory()->create([
            'user_id' => $user->id,
            'name' => 'Older project',
            'starts_on' => '2026-06-01',
            'expected_ends_on' => '2026-06-15',
        ]);
        $newerProject = Project::factory()->create([
            'user_id' => $user->id,
            'name' => 'Newer project',
            'starts_on' => '2026-06-01',
            'expected_ends_on' => '2026-06-30',
        ]);

        Task::factory()->forProject($olderProject)->create([
            'task_date' => '2026-06-15',
            'status' => 'in_progress',
        ]);
        Task::factory()->forProject($newerProject)->create([
            'task_date' => '2026-06-20',
            'status' => 'in_progress',
        ]);

        Passport::actingAs($user);

        $response = $this->getJson('/api/reports?report_type=both&start_date=2026-06-01&end_date=2026-06-30');

        $response->assertOk()
            ->assertJsonCount(2, 'data.projects')
            ->assertJsonPath('data.projects.0.name', 'Newer project')
            ->assertJsonPath('data.projects.1.name', 'Older project')
            ->assertJsonCount(2, 'data.tasks')
            ->assertJsonPath('data.tasks.0.task_date', '2026-06-20')
            ->assertJsonPath('data.tasks.1.task_date', '2026-06-15');
    }

    public function test_report_requires_valid_filters(): void
    {
        Passport::actingAs(User::factory()->create());

        $response = $this->getJson('/api/reports?report_type=invalid&start_date=2026-06-20&end_date=2026-06-01&status=done');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['report_type', 'status', 'end_date']);
    }
}
