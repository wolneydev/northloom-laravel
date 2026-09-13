<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Mcp\Servers\HospitableServer;
use App\Mcp\Tools\SuggestCreationIdeaTool;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class CreationIdeaTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_request_a_creation_idea(): void
    {
        $this->getJson('/api/creation-ideas?target=project')
            ->assertUnauthorized();
    }

    public function test_creation_idea_requires_valid_target(): void
    {
        Passport::actingAs(User::factory()->create());

        $this->getJson('/api/creation-ideas?target=note')
            ->assertStatus(422)
            ->assertJsonValidationErrors('target');
    }

    public function test_task_idea_requires_project_id(): void
    {
        Passport::actingAs(User::factory()->create());

        $this->getJson('/api/creation-ideas?target=task')
            ->assertStatus(422)
            ->assertJsonValidationErrors('project_id');
    }

    public function test_task_idea_rejects_foreign_project(): void
    {
        $foreignProject = Project::factory()->create();

        Passport::actingAs(User::factory()->create());

        $this->getJson('/api/creation-ideas?target=task&project_id='.$foreignProject->id)
            ->assertStatus(422)
            ->assertJsonValidationErrors('project_id');
    }

    public function test_creation_idea_rejects_invalid_at(): void
    {
        Passport::actingAs(User::factory()->create());

        $this->getJson('/api/creation-ideas?target=project&at=not-a-date')
            ->assertStatus(422)
            ->assertJsonValidationErrors('at');
    }

    public function test_sunday_night_spring_returns_growth_project_copy(): void
    {
        Passport::actingAs(User::factory()->create());

        $response = $this->getJson('/api/creation-ideas?target=project&at=2026-09-13T21:30:00-03:00');

        $response->assertOk()
            ->assertJsonPath('data.target', 'project')
            ->assertJsonPath('data.headline', 'Protótipo de uma ideia ainda não explorada')
            ->assertJsonPath('data.suggestion', 'Use este momento para crescimento e imaginação. Crie um projeto em torno de uma ideia que você ainda não testou, com o objetivo de validar uma versão pequena na semana que começa.')
            ->assertJsonPath('data.field_hints.headline', 'name')
            ->assertJsonPath('data.field_hints.suggestion', 'notes')
            ->assertJsonPath('data.context.country', 'BR')
            ->assertJsonPath('data.context.timezone', 'America/Sao_Paulo')
            ->assertJsonPath('data.context.local_date_time', '2026-09-13T21:30:00-03:00')
            ->assertJsonPath('data.context.day_period', 'night')
            ->assertJsonPath('data.context.season', 'spring')
            ->assertJsonPath('data.context.weekday', 'sunday')
            ->assertJsonPath('data.context.task_count_on_day', 0)
            ->assertJsonPath('data.context.upcoming_task_count', 0)
            ->assertJsonPath('data.context.lunar_phase', null)
            ->assertJsonPath('data.context.primary_mode', 'growth')
            ->assertJsonPath('data.context.secondary_mode', 'imagination');
    }

    public function test_monday_morning_winter_returns_planning_project_copy(): void
    {
        Passport::actingAs(User::factory()->create());

        $this->getJson('/api/creation-ideas?target=project&at=2026-06-15T08:00:00-03:00')
            ->assertOk()
            ->assertJsonPath('data.headline', 'Prioridades da semana que começa')
            ->assertJsonPath('data.suggestion', 'Use este momento para planejamento. Organize um projeto em torno das prioridades da semana, definindo o que precisa ser decidido antes de agir.')
            ->assertJsonPath('data.context.day_period', 'morning')
            ->assertJsonPath('data.context.season', 'winter')
            ->assertJsonPath('data.context.weekday', 'monday')
            ->assertJsonPath('data.context.primary_mode', 'planning')
            ->assertJsonPath('data.context.secondary_mode', 'planning');
    }

    public function test_task_idea_on_owned_project_is_execution_oriented(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        Task::factory()->forProject($project)->create(['task_date' => '2026-01-10']);

        Passport::actingAs($user);

        $this->getJson('/api/creation-ideas?target=task&project_id='.$project->id.'&at=2026-01-14T15:00:00-03:00')
            ->assertOk()
            ->assertJsonPath('data.target', 'task')
            ->assertJsonPath('data.headline', 'Próxima ação de maior impacto')
            ->assertJsonPath('data.suggestion', 'Use este momento para execução. Avance agora a tarefa de maior impacto deste projeto, enquanto o ritmo da tarde ainda favorece progresso concreto.')
            ->assertJsonPath('data.field_hints.headline', 'title')
            ->assertJsonPath('data.field_hints.suggestion', 'notes')
            ->assertJsonPath('data.context.day_period', 'afternoon')
            ->assertJsonPath('data.context.season', 'summer')
            ->assertJsonPath('data.context.weekday', 'wednesday')
            ->assertJsonPath('data.context.primary_mode', 'action')
            ->assertJsonPath('data.context.secondary_mode', 'execution');
    }

    public function test_context_detects_night_after_midnight(): void
    {
        Passport::actingAs(User::factory()->create());

        $this->getJson('/api/creation-ideas?target=project&at=2026-09-14T03:00:00-03:00')
            ->assertOk()
            ->assertJsonPath('data.context.day_period', 'night')
            ->assertJsonPath('data.context.weekday', 'monday');
    }

    public function test_idea_succeeds_when_calendar_is_empty(): void
    {
        Passport::actingAs(User::factory()->create());

        $response = $this->getJson('/api/creation-ideas?target=project&at=2026-09-13T21:30:00-03:00');

        $response->assertOk()
            ->assertJsonPath('data.context.task_count_on_day', 0)
            ->assertJsonPath('data.context.upcoming_task_count', 0);

        $this->assertGreaterThanOrEqual(1, substr_count((string) $response->json('data.suggestion'), '.'));
        $this->assertLessThanOrEqual(3, substr_count((string) $response->json('data.suggestion'), '.'));
        $this->assertLessThanOrEqual(255, mb_strlen((string) $response->json('data.headline')));
    }

    public function test_busy_friday_refines_copy_from_calendar_without_failing(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        Task::factory()->forProject($project)->count(3)->create(['task_date' => '2026-04-10']);
        Task::factory()->create(['task_date' => '2026-04-10']);

        Passport::actingAs($user);

        $this->getJson('/api/creation-ideas?target=project&at=2026-04-10T15:00:00-03:00')
            ->assertOk()
            ->assertJsonPath('data.context.task_count_on_day', 3)
            ->assertJsonPath('data.context.upcoming_task_count', 3)
            ->assertJsonPath('data.context.season', 'autumn')
            ->assertJsonPath('data.context.weekday', 'friday')
            ->assertJsonPath('data.headline', 'Revisão do que já está na agenda');
    }

    public function test_creation_idea_does_not_persist_resources(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        $task = Task::factory()->forProject($project)->create([
            'notify' => false,
            'notify_at_datetime' => null,
        ]);

        Passport::actingAs($user);

        $this->getJson('/api/creation-ideas?target=project&at=2026-09-13T21:30:00-03:00')
            ->assertOk();

        $this->assertDatabaseCount('projects', 1);
        $this->assertDatabaseCount('tasks', 1);
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'notify' => false,
            'notify_at_datetime' => null,
        ]);
    }

    public function test_mcp_server_registers_suggest_creation_idea_tool(): void
    {
        $tools = (new \ReflectionProperty(HospitableServer::class, 'tools'))->getDefaultValue();

        $this->assertContains(SuggestCreationIdeaTool::class, $tools);
    }
}
