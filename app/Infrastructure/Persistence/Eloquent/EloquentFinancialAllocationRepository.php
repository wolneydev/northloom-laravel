<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\Financials\Repositories\FinancialAllocationRepositoryInterface;
use App\Models\FinancialAllocation;

final class EloquentFinancialAllocationRepository implements FinancialAllocationRepositoryInterface
{
    public function create(int $taskId, int $fundId, string $amount): FinancialAllocation
    {
        return FinancialAllocation::query()->create([
            'task_id' => $taskId,
            'fund_id' => $fundId,
            'amount' => $amount,
            'allocated_at' => now(),
        ])->load(['task.project', 'fund.project']);
    }
}
