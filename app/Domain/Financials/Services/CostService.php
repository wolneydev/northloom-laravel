<?php

declare(strict_types=1);

namespace App\Domain\Financials\Services;

use App\Domain\Financials\DTOs\CostData;
use App\Domain\Financials\Exceptions\ProjectCurrencyNotConfiguredException;
use App\Domain\Financials\Repositories\CostRepositoryInterface;
use App\Models\Cost;
use App\Models\Project;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final readonly class CostService
{
    public function __construct(private CostRepositoryInterface $costs) {}

    public function listForProject(int $projectId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->costs->paginateForProject($projectId, $perPage);
    }

    public function create(Project $project, CostData $data): Cost
    {
        if ($project->currency === null) {
            throw new ProjectCurrencyNotConfiguredException;
        }

        return $this->costs->create($data)->load('project');
    }
}
