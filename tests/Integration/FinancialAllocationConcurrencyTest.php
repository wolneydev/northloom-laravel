<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Domain\Financials\DTOs\FinancialAllocationData;
use App\Domain\Financials\Services\FinancialAllocationService;
use App\Models\FinancialAllocation;
use App\Models\Fund;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class FinancialAllocationConcurrencyTest extends TestCase
{
    use DatabaseMigrations;

    public function test_competing_allocations_cannot_overspend_postgresql_balance(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            self::markTestSkipped('PostgreSQL is required to validate row-level locking.');
        }

        if (! function_exists('pcntl_fork')) {
            self::markTestSkipped('The pcntl extension is required for the concurrency test.');
        }

        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        $task = Task::factory()->forProject($project)->create();
        $fund = Fund::factory()->create([
            'project_id' => $project->id,
            'opening_balance' => '100.00',
            'available_balance' => '100.00',
        ]);
        DB::disconnect();

        $files = [tempnam(sys_get_temp_dir(), 'allocation-a-'), tempnam(sys_get_temp_dir(), 'allocation-b-')];
        $children = [];

        foreach ($files as $file) {
            $pid = pcntl_fork();
            if ($pid === 0) {
                DB::reconnect();
                try {
                    app(FinancialAllocationService::class)->allocate(
                        Task::query()->findOrFail($task->id),
                        new FinancialAllocationData($task->id, $fund->id, '75.00', $user->id),
                    );
                    file_put_contents($file, 'success');
                } catch (\Throwable) {
                    file_put_contents($file, 'rejected');
                }
                exit(0);
            }
            $children[] = $pid;
        }

        foreach ($children as $pid) {
            pcntl_waitpid($pid, $status);
        }

        DB::reconnect();
        $results = array_map(static fn (string $file): string => (string) file_get_contents($file), $files);
        array_map('unlink', $files);

        self::assertSame(1, count(array_filter($results, static fn (string $result): bool => $result === 'success')));
        self::assertSame('25.00', Fund::query()->findOrFail($fund->id)->available_balance);
        self::assertSame(1, FinancialAllocation::query()->count());
    }
}
