<?php

declare(strict_types=1);

namespace App\Domain\Financials\DTOs;

final readonly class CostData
{
    public function __construct(
        public int $project_id,
        public string $amount,
        public string $description,
        public string $incurred_on,
    ) {}
}
