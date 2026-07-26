<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\Financials\DTOs\FundData;
use App\Domain\Financials\Repositories\FundRepositoryInterface;
use App\Models\Fund;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentFundRepository implements FundRepositoryInterface
{
    public function paginateForProject(int $projectId, int $perPage = 15): LengthAwarePaginator
    {
        return Fund::query()
            ->with('project')
            ->where('project_id', $projectId)
            ->latest()
            ->paginate($perPage);
    }

    public function create(FundData $data): Fund
    {
        return Fund::query()->create([
            'project_id' => $data->project_id,
            'name' => $data->name,
            'opening_balance' => $data->opening_balance,
            'available_balance' => $data->opening_balance,
        ]);
    }

    public function findByIdForUpdate(int $id): ?Fund
    {
        return Fund::query()->with('project')->lockForUpdate()->find($id);
    }

    public function decreaseAvailableBalance(int $id, string $amount): bool
    {
        return Fund::query()
            ->whereKey($id)
            ->where('available_balance', '>=', $amount)
            ->decrement('available_balance', $amount) === 1;
    }
}
