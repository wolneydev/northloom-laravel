<?php

declare(strict_types=1);

namespace App\Domain\Financials\Repositories;

use App\Models\FinancialAllocation;

interface FinancialAllocationRepositoryInterface
{
    public function create(int $taskId, int $fundId, string $amount): FinancialAllocation;
}
