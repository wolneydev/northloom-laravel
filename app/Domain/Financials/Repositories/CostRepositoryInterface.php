<?php

declare(strict_types=1);

namespace App\Domain\Financials\Repositories;

use App\Domain\Financials\DTOs\CostData;
use App\Models\Cost;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface CostRepositoryInterface
{
    public function paginateForProject(int $projectId, int $perPage = 15): LengthAwarePaginator;

    public function create(CostData $data): Cost;
}
