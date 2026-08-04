<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Fund;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

final class FundTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_and_list_funds_with_exact_balances(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        Passport::actingAs($user);

        $this->postJson("/api/projects/{$project->id}/funds", [
            'name' => 'Operating budget',
            'opening_balance' => '1000.00',
        ])->assertCreated()
            ->assertJsonPath('data.currency', 'BRL')
            ->assertJsonPath('data.opening_balance', '1000.00')
            ->assertJsonPath('data.available_balance', '1000.00');

        Fund::factory()->create(['project_id' => $project->id, 'opening_balance' => '2.10', 'available_balance' => '2.10']);

        $this->getJson("/api/projects/{$project->id}/funds?per_page=1")
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('meta.per_page', 1);
    }

    public function test_funds_require_authentication_ownership_and_valid_data(): void
    {
        $project = Project::factory()->create();

        $this->getJson("/api/projects/{$project->id}/funds")->assertUnauthorized();

        Passport::actingAs(User::factory()->create());
        $this->getJson("/api/projects/{$project->id}/funds")->assertForbidden();
        $this->postJson("/api/projects/{$project->id}/funds", [
            'name' => '',
            'opening_balance' => '-1.00',
        ])->assertForbidden();
    }

    public function test_owner_receives_validation_errors_for_invalid_fund(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        Passport::actingAs($user);

        foreach (['1', '1.2', '1.001'] as $balance) {
            $this->postJson("/api/projects/{$project->id}/funds", [
                'name' => '',
                'opening_balance' => $balance,
            ])->assertUnprocessable()->assertJsonValidationErrors(['name', 'opening_balance']);
        }
    }

    public function test_unconfigured_legacy_project_cannot_create_a_fund(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id, 'currency' => null]);
        Passport::actingAs($user);

        $this->postJson("/api/projects/{$project->id}/funds", [
            'name' => 'Operating budget',
            'opening_balance' => '1000.00',
        ])->assertUnprocessable()->assertJsonValidationErrors('currency');

        $this->assertDatabaseCount('funds', 0);
    }
}
