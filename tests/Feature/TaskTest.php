<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_a_task_linked_to_a_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        Passport::actingAs($user);

        $response = $this->postJson('/api/tasks', [
            'project_id' => $project->id,
            'title' => 'Modelar banco de dados',
            'task_date' => '2026-06-17',
            'starts_at' => '2026-06-17 09:00:00',
            'ends_at' => '2026-06-17 11:00:00',
            'notes' => 'Criar tabelas principais',
            'location' => 'Home office',
            'priority' => 'high',
            'status' => 'pending',
            'notify' => true,
            'notify_minutes_before' => 30,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.title', 'Modelar banco de dados')
            ->assertJsonPath('data.project_id', $project->id)
            ->assertJsonPath('data.project_name', $project->name)
            ->assertJsonPath('data.notify', true)
            ->assertJsonPath('data.starts_at', '2026-06-17T09:00:00Z');

        $this->assertDatabaseHas('tasks', [
            'title' => 'Modelar banco de dados',
            'user_id' => $user->id,
            'project_id' => $project->id,
        ]);
    }

    public function test_user_can_create_a_task_using_time_only_and_portuguese_labels(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        Passport::actingAs($user);

        $response = $this->postJson('/api/tasks', [
            'project_id' => $project->id,
            'title' => 'Preply platform',
            'task_date' => '2026-06-17',
            'starts_at' => '11:30',
            'ends_at' => '12:20',
            'notes' => 'Class with Hayley teacher',
            'location' => 'Online',
            'priority' => 'media',
            'status' => 'pendente',
            'notify' => false,
            'notify_minutes_before' => null,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.starts_at', '2026-06-17T11:30:00Z')
            ->assertJsonPath('data.ends_at', '2026-06-17T12:20:00Z')
            ->assertJsonPath('data.priority', 'medium')
            ->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('tasks', [
            'title' => 'Preply platform',
            'priority' => 'medium',
            'status' => 'pending',
        ]);
    }

    public function test_create_task_fails_when_ends_at_is_before_starts_at(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        Passport::actingAs($user);

        $response = $this->postJson('/api/tasks', [
            'project_id' => $project->id,
            'title' => 'Tarefa inválida',
            'task_date' => '2026-06-17',
            'starts_at' => '2026-06-17 11:00:00',
            'ends_at' => '2026-06-17 09:00:00',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('ends_at');
    }

    public function test_user_cannot_create_task_in_another_users_project(): void
    {
        $foreignProject = Project::factory()->create();

        Passport::actingAs(User::factory()->create());

        $response = $this->postJson('/api/tasks', [
            'project_id' => $foreignProject->id,
            'title' => 'Invasão',
            'task_date' => '2026-06-17',
            'starts_at' => '2026-06-17 09:00:00',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('project_id');
    }

    public function test_user_can_list_tasks_within_a_date_range(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        Task::factory()->forProject($project)->create(['task_date' => '2026-06-10']);
        Task::factory()->forProject($project)->create(['task_date' => '2026-06-15']);
        Task::factory()->forProject($project)->create(['task_date' => '2026-06-25']);

        Passport::actingAs($user);

        $response = $this->getJson('/api/tasks?start=2026-06-12&end=2026-06-20');

        $response->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.task_date', '2026-06-15');
    }

    public function test_user_can_filter_tasks_by_project(): void
    {
        $user = User::factory()->create();
        $projectA = Project::factory()->create(['user_id' => $user->id]);
        $projectB = Project::factory()->create(['user_id' => $user->id]);

        Task::factory()->forProject($projectA)->count(2)->create();
        Task::factory()->forProject($projectB)->count(3)->create();

        Passport::actingAs($user);

        $response = $this->getJson("/api/tasks?project_id={$projectB->id}");

        $response->assertOk()->assertJsonCount(3, 'data');
    }

    public function test_user_only_lists_their_own_tasks(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        Task::factory()->forProject($project)->count(2)->create();
        Task::factory()->count(4)->create();

        Passport::actingAs($user);

        $this->getJson('/api/tasks')->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_user_can_update_their_task(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        $task = Task::factory()->forProject($project)->create();

        Passport::actingAs($user);

        $response = $this->putJson("/api/tasks/{$task->id}", [
            'title' => 'Título atualizado',
            'status' => 'completed',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.title', 'Título atualizado')
            ->assertJsonPath('data.status', 'completed');
    }

    public function test_user_can_delete_their_task(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        $task = Task::factory()->forProject($project)->create();

        Passport::actingAs($user);

        $this->deleteJson("/api/tasks/{$task->id}")->assertNoContent();
        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    public function test_user_cannot_access_another_users_task(): void
    {
        $task = Task::factory()->create();

        Passport::actingAs(User::factory()->create());

        $this->getJson("/api/tasks/{$task->id}")->assertForbidden();
        $this->deleteJson("/api/tasks/{$task->id}")->assertForbidden();
    }
}
