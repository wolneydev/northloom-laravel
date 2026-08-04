<?php

declare(strict_types=1);

namespace App\Domain\Financials\DTOs;

final readonly class FundData
{
    public function __construct(
        public int $project_id,
        public string $name,
        public string $opening_balance,
    ) {}
}
