<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Fund;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

final class CostTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_register_and_list_cost_without_debiting_funds(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        $fund = Fund::factory()->create(['project_id' => $project->id, 'available_balance' => '1000.00']);
        Passport::actingAs($user);

        $this->postJson("/api/projects/{$project->id}/costs", [
            'amount' => '125.50',
            'description' => 'Venue deposit',
            'incurred_on' => '2026-07-26',
        ])->assertCreated()
            ->assertJsonPath('data.currency', 'BRL')
            ->assertJsonPath('data.amount', '125.50')
            ->assertJsonPath('data.incurred_on', '2026-07-26');

        $this->getJson("/api/projects/{$project->id}/costs?per_page=1")
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('meta.per_page', 1);
        self::assertSame('1000.00', $fund->refresh()->available_balance);
    }

    public function test_costs_enforce_authentication_ownership_and_validation(): void
    {
        $project = Project::factory()->create();
        $this->getJson("/api/projects/{$project->id}/costs")->assertUnauthorized();

        Passport::actingAs(User::factory()->create());
        $this->getJson("/api/projects/{$project->id}/costs")->assertForbidden();

        Passport::actingAs($project->user);
        $this->postJson("/api/projects/{$project->id}/costs", [
            'amount' => '0.00',
            'description' => '',
            'incurred_on' => 'not-a-date',
        ])->assertUnprocessable()->assertJsonValidationErrors(['amount', 'description', 'incurred_on']);

        foreach (['1', '1.2', '1.001'] as $amount) {
            $this->postJson("/api/projects/{$project->id}/costs", [
                'amount' => $amount,
                'description' => 'Invalid precision',
                'incurred_on' => '2026-07-26',
            ])->assertUnprocessable()->assertJsonValidationErrors('amount');
        }
    }

    public function test_unconfigured_legacy_project_cannot_register_a_cost(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id, 'currency' => null]);
        Passport::actingAs($user);

        $this->postJson("/api/projects/{$project->id}/costs", [
            'amount' => '10.00',
            'description' => 'Venue deposit',
            'incurred_on' => '2026-07-26',
        ])->assertUnprocessable()->assertJsonValidationErrors('currency');

        $this->assertDatabaseCount('costs', 0);
    }
}
