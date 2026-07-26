<?php

declare(strict_types=1);

namespace App\Domain\Financials\Repositories;

use App\Domain\Financials\DTOs\FundData;
use App\Models\Fund;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface FundRepositoryInterface
{
    public function paginateForProject(int $projectId, int $perPage = 15): LengthAwarePaginator;

    public function create(FundData $data): Fund;

    public function findByIdForUpdate(int $id): ?Fund;

    public function decreaseAvailableBalance(int $id, string $amount): bool;
}
