<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\Financials\DTOs\CostData;
use App\Domain\Financials\Repositories\CostRepositoryInterface;
use App\Models\Cost;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentCostRepository implements CostRepositoryInterface
{
    public function paginateForProject(int $projectId, int $perPage = 15): LengthAwarePaginator
    {
        return Cost::query()
            ->with('project')
            ->where('project_id', $projectId)
            ->orderByDesc('incurred_on')
            ->latest('id')
            ->paginate($perPage);
    }

    public function create(CostData $data): Cost
    {
        return Cost::query()->create([
            'project_id' => $data->project_id,
            'amount' => $data->amount,
            'description' => $data->description,
            'incurred_on' => $data->incurred_on,
        ]);
    }
}
