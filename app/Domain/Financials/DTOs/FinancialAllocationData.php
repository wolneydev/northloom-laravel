<?php

declare(strict_types=1);

namespace App\Domain\Financials\DTOs;

final readonly class FinancialAllocationData
{
    public function __construct(
        public int $task_id,
        public int $fund_id,
        public string $amount,
        public int $actor_user_id,
    ) {}
}
