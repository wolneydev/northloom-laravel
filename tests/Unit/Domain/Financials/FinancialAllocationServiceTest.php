<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Financials;

use App\Domain\Financials\Contracts\UnitOfWorkInterface;
use App\Domain\Financials\DTOs\FinancialAllocationData;
use App\Domain\Financials\Exceptions\FundProjectMismatchException;
use App\Domain\Financials\Repositories\FinancialAllocationRepositoryInterface;
use App\Domain\Financials\Repositories\FundRepositoryInterface;
use App\Domain\Financials\Services\FinancialAllocationService;
use App\Models\Fund;
use App\Models\Project;
use App\Models\Task;
use Mockery;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class FinancialAllocationServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_project_mismatch_is_checked_before_allocation_or_debit(): void
    {
        $funds = Mockery::mock(FundRepositoryInterface::class);
        $allocations = Mockery::mock(FinancialAllocationRepositoryInterface::class);
        $unitOfWork = $this->immediateUnitOfWork();
        $task = new Task(['user_id' => 5, 'project_id' => 10]);
        $task->id = 20;
        $project = new Project(['user_id' => 5, 'currency' => 'BRL']);
        $fund = new Fund(['project_id' => 11, 'available_balance' => '100.00']);
        $fund->id = 30;
        $fund->setRelation('project', $project);

        $funds->shouldReceive('findByIdForUpdate')->once()->with(30)->andReturn($fund);
        $allocations->shouldNotReceive('create');
        $funds->shouldNotReceive('decreaseAvailableBalance');

        $this->expectException(FundProjectMismatchException::class);

        (new FinancialAllocationService($funds, $allocations, $unitOfWork))
            ->allocate($task, new FinancialAllocationData(20, 30, '10.00', 5));
    }

    public function test_repository_failure_escapes_transaction_and_prevents_debit(): void
    {
        $funds = Mockery::mock(FundRepositoryInterface::class);
        $allocations = Mockery::mock(FinancialAllocationRepositoryInterface::class);
        $task = new Task(['user_id' => 5, 'project_id' => 10]);
        $task->id = 20;
        $project = new Project(['user_id' => 5, 'currency' => 'BRL']);
        $fund = new Fund(['project_id' => 10, 'available_balance' => '100.00']);
        $fund->id = 30;
        $fund->setRelation('project', $project);

        $funds->shouldReceive('findByIdForUpdate')->once()->andReturn($fund);
        $allocations->shouldReceive('create')->once()->andThrow(new RuntimeException('insert failed'));
        $funds->shouldNotReceive('decreaseAvailableBalance');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('insert failed');

        (new FinancialAllocationService($funds, $allocations, $this->immediateUnitOfWork()))
            ->allocate($task, new FinancialAllocationData(20, 30, '10.00', 5));
    }

    private function immediateUnitOfWork(): UnitOfWorkInterface
    {
        return new class implements UnitOfWorkInterface
        {
            public function transaction(callable $callback): mixed
            {
                return $callback();
            }
        };
    }
}
