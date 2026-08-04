<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Financials\Repositories\FundRepositoryInterface;
use App\Models\FinancialAllocation;
use App\Models\Fund;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Mockery;
use RuntimeException;
use Tests\TestCase;

final class FinancialAllocationTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_atomically_allocate_an_exact_amount(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        $task = Task::factory()->forProject($project)->create();
        $fund = Fund::factory()->create([
            'project_id' => $project->id,
            'opening_balance' => '1000.00',
            'available_balance' => '1000.00',
        ]);
        Passport::actingAs($user);

        $this->postJson("/api/tasks/{$task->id}/financial-allocations", [
            'fund_id' => $fund->id,
            'amount' => '250.00',
        ])->assertCreated()
            ->assertJsonPath('data.currency', 'BRL')
            ->assertJsonPath('data.amount', '250.00');

        self::assertSame('750.00', $fund->refresh()->available_balance);
        $this->assertDatabaseCount('financial_allocations', 1);
    }

    public function test_rejections_leave_all_financial_state_unchanged(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        $task = Task::factory()->forProject($project)->create();
        $fund = Fund::factory()->create(['project_id' => $project->id, 'available_balance' => '10.00']);
        Passport::actingAs($user);

        $this->postJson("/api/tasks/{$task->id}/financial-allocations", [
            'fund_id' => $fund->id,
            'amount' => '10.01',
        ])->assertUnprocessable()->assertJsonValidationErrors('amount');

        self::assertSame('10.00', $fund->refresh()->available_balance);
        $this->assertDatabaseCount('financial_allocations', 0);
    }

    public function test_transaction_rolls_back_allocation_and_debit_when_persistence_fails(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        $task = Task::factory()->forProject($project)->create();
        $fund = Fund::factory()->create(['project_id' => $project->id, 'available_balance' => '100.00']);
        Passport::actingAs($user);

        $repository = Mockery::mock(FundRepositoryInterface::class);
        $repository->shouldReceive('findByIdForUpdate')
            ->once()
            ->andReturnUsing(fn (): Fund => Fund::query()->with('project')->lockForUpdate()->findOrFail($fund->id));
        $repository->shouldReceive('decreaseAvailableBalance')
            ->once()
            ->andReturnUsing(function () use ($fund): never {
                Fund::query()->whereKey($fund->id)->update(['available_balance' => '75.00']);
                throw new RuntimeException('simulated persistence failure');
            });
        $this->app->instance(FundRepositoryInterface::class, $repository);
        $this->withoutExceptionHandling();

        try {
            $this->postJson("/api/tasks/{$task->id}/financial-allocations", [
                'fund_id' => $fund->id,
                'amount' => '25.00',
            ]);
            self::fail('The simulated failure should escape the transaction.');
        } catch (RuntimeException $exception) {
            self::assertSame('simulated persistence failure', $exception->getMessage());
        }

        self::assertSame('100.00', $fund->refresh()->available_balance);
        $this->assertDatabaseCount('financial_allocations', 0);
    }

    public function test_same_owner_project_mismatch_is_unprocessable(): void
    {
        $user = User::factory()->create();
        $taskProject = Project::factory()->create(['user_id' => $user->id]);
        $otherProject = Project::factory()->create(['user_id' => $user->id]);
        $task = Task::factory()->forProject($taskProject)->create();
        $fund = Fund::factory()->create(['project_id' => $otherProject->id, 'available_balance' => '50.00']);
        Passport::actingAs($user);

        $this->postJson("/api/tasks/{$task->id}/financial-allocations", [
            'fund_id' => $fund->id,
            'amount' => '5.00',
        ])->assertUnprocessable()->assertJsonValidationErrors('fund_id');

        self::assertSame('50.00', $fund->refresh()->available_balance);
    }

    public function test_foreign_resources_are_forbidden_and_missing_fund_is_not_found(): void
    {
        $owner = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $owner->id]);
        $task = Task::factory()->forProject($project)->create();
        $foreignFund = Fund::factory()->create(['available_balance' => '10.00']);
        Passport::actingAs(User::factory()->create());

        $this->postJson("/api/tasks/{$task->id}/financial-allocations", [
            'fund_id' => 999999,
            'amount' => '1.00',
        ])->assertForbidden();

        Passport::actingAs($owner);
        $this->postJson("/api/tasks/{$task->id}/financial-allocations", [
            'fund_id' => $foreignFund->id,
            'amount' => '1.00',
        ])->assertForbidden();

        $this->postJson("/api/tasks/{$task->id}/financial-allocations", [
            'fund_id' => 999999,
            'amount' => '1.00',
        ])->assertNotFound();
    }

    public function test_invalid_amount_is_rejected(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        $task = Task::factory()->forProject($project)->create();
        Passport::actingAs($user);

        foreach (['0.00', '-1.00', '1', '1.2', '1.001'] as $amount) {
            $this->postJson("/api/tasks/{$task->id}/financial-allocations", [
                'fund_id' => 1,
                'amount' => $amount,
            ])->assertUnprocessable()->assertJsonValidationErrors('amount');
        }

        self::assertSame(0, FinancialAllocation::query()->count());
    }

    public function test_unconfigured_legacy_project_cannot_allocate_funds(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id, 'currency' => null]);
        $task = Task::factory()->forProject($project)->create();
        $fund = Fund::factory()->create(['project_id' => $project->id, 'available_balance' => '10.00']);
        Passport::actingAs($user);

        $this->postJson("/api/tasks/{$task->id}/financial-allocations", [
            'fund_id' => $fund->id,
            'amount' => '1.00',
        ])->assertUnprocessable()->assertJsonValidationErrors('currency');

        self::assertSame('10.00', $fund->refresh()->available_balance);
        $this->assertDatabaseCount('financial_allocations', 0);
    }
}
