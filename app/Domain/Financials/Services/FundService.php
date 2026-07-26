<?php

declare(strict_types=1);

namespace App\Domain\Financials\Services;

use App\Domain\Financials\DTOs\FundData;
use App\Domain\Financials\Exceptions\ProjectCurrencyNotConfiguredException;
use App\Domain\Financials\Repositories\FundRepositoryInterface;
use App\Models\Fund;
use App\Models\Project;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final readonly class FundService
{
    public function __construct(private FundRepositoryInterface $funds) {}

    public function listForProject(int $projectId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->funds->paginateForProject($projectId, $perPage);
    }

    public function create(Project $project, FundData $data): Fund
    {
        if ($project->currency === null) {
            throw new ProjectCurrencyNotConfiguredException;
        }

        return $this->funds->create($data)->load('project');
    }
}
