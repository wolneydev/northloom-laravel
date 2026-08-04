<?php

declare(strict_types=1);

namespace App\Domain\Financials\Services;

use App\Domain\Financials\Contracts\UnitOfWorkInterface;
use App\Domain\Financials\DTOs\FinancialAllocationData;
use App\Domain\Financials\Exceptions\FinancialOwnershipException;
use App\Domain\Financials\Exceptions\FundProjectMismatchException;
use App\Domain\Financials\Exceptions\InsufficientFundBalanceException;
use App\Domain\Financials\Exceptions\ProjectCurrencyNotConfiguredException;
use App\Domain\Financials\Repositories\FinancialAllocationRepositoryInterface;
use App\Domain\Financials\Repositories\FundRepositoryInterface;
use App\Domain\Financials\ValueObjects\Money;
use App\Models\FinancialAllocation;
use App\Models\Fund;
use App\Models\Task;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final readonly class FinancialAllocationService
{
    public function __construct(
        private FundRepositoryInterface $funds,
        private FinancialAllocationRepositoryInterface $allocations,
        private UnitOfWorkInterface $unitOfWork,
    ) {}

    public function allocate(Task $task, FinancialAllocationData $data): FinancialAllocation
    {
        if ($task->id !== $data->task_id || $task->user_id !== $data->actor_user_id) {
            throw new FinancialOwnershipException;
        }

        $amount = Money::fromString($data->amount);

        return $this->unitOfWork->transaction(function () use ($task, $data, $amount): FinancialAllocation {
            $fund = $this->funds->findByIdForUpdate($data->fund_id);

            if (! $fund instanceof Fund) {
                throw (new ModelNotFoundException)->setModel(Fund::class, [$data->fund_id]);
            }

            if ($fund->project->user_id !== $data->actor_user_id) {
                throw new FinancialOwnershipException;
            }

            if ($fund->project_id !== $task->project_id) {
                throw new FundProjectMismatchException;
            }

            if ($fund->project->currency === null) {
                throw new ProjectCurrencyNotConfiguredException;
            }

            if (! Money::fromString($fund->available_balance)->isGreaterThanOrEqualTo($amount)) {
                throw new InsufficientFundBalanceException;
            }

            $allocation = $this->allocations->create($task->id, $fund->id, $amount->toString());

            if (! $this->funds->decreaseAvailableBalance($fund->id, $amount->toString())) {
                throw new InsufficientFundBalanceException;
            }

            return $allocation;
        });
    }
}
