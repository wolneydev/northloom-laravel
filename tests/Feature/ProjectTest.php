<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Fund;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_a_project(): void
    {
        Passport::actingAs($user = User::factory()->create());

        $response = $this->postJson('/api/projects', [
            'name' => 'Projeto ERP',
            'starts_on' => '2026-06-16',
            'expected_ends_on' => '2026-07-30',
            'notes' => 'Observações gerais',
            'currency' => 'brl',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Projeto ERP')
            ->assertJsonPath('data.starts_on', '2026-06-16')
            ->assertJsonPath('data.expected_ends_on', '2026-07-30')
            ->assertJsonPath('data.currency', 'BRL');

        $this->assertDatabaseHas('projects', [
            'name' => 'Projeto ERP',
            'user_id' => $user->id,
            'currency' => 'BRL',
        ]);
    }

    public function test_create_project_fails_when_end_is_before_start(): void
    {
        Passport::actingAs(User::factory()->create());

        $response = $this->postJson('/api/projects', [
            'name' => 'Projeto inválido',
            'starts_on' => '2026-07-30',
            'expected_ends_on' => '2026-06-16',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('expected_ends_on');
    }

    public function test_project_requires_a_supported_currency(): void
    {
        Passport::actingAs(User::factory()->create());

        $payload = [
            'name' => 'Projeto',
            'starts_on' => '2026-07-01',
            'expected_ends_on' => '2026-07-30',
        ];

        $this->postJson('/api/projects', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('currency');

        $this->postJson('/api/projects', [...$payload, 'currency' => 'ZZZ'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('currency');
    }

    public function test_user_lists_only_their_own_projects(): void
    {
        $user = User::factory()->create();
        Project::factory()->count(2)->create(['user_id' => $user->id]);
        Project::factory()->count(3)->create();

        Passport::actingAs($user);

        $response = $this->getJson('/api/projects');

        $response->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_user_can_update_their_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        Passport::actingAs($user);

        $response = $this->putJson("/api/projects/{$project->id}", [
            'name' => 'Nome atualizado',
        ]);

        $response->assertOk()->assertJsonPath('data.name', 'Nome atualizado');
        $this->assertDatabaseHas('projects', ['id' => $project->id, 'name' => 'Nome atualizado']);
    }

    public function test_owner_can_configure_legacy_currency_before_financial_activity(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id, 'currency' => null]);
        Passport::actingAs($user);

        $this->putJson("/api/projects/{$project->id}", ['currency' => 'usd'])
            ->assertOk()
            ->assertJsonPath('data.currency', 'USD');

        $this->assertDatabaseHas('projects', ['id' => $project->id, 'currency' => 'USD']);
    }

    public function test_currency_cannot_change_after_financial_activity(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id, 'currency' => 'BRL']);
        Fund::factory()->create(['project_id' => $project->id]);
        Passport::actingAs($user);

        $this->putJson("/api/projects/{$project->id}", ['currency' => 'USD'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('currency');

        self::assertSame('BRL', $project->refresh()->currency);
    }

    public function test_user_can_delete_their_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        Passport::actingAs($user);

        $this->deleteJson("/api/projects/{$project->id}")->assertNoContent();
        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
    }

    public function test_user_cannot_access_another_users_project(): void
    {
        $project = Project::factory()->create();

        Passport::actingAs(User::factory()->create());

        $this->getJson("/api/projects/{$project->id}")->assertForbidden();
        $this->putJson("/api/projects/{$project->id}", ['name' => 'x'])->assertForbidden();
        $this->deleteJson("/api/projects/{$project->id}")->assertForbidden();
    }
}
