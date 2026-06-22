<?php

declare(strict_types=1);

namespace App\Domain\Projects\Repositories;

use App\Domain\Projects\DTOs\ProjectData;
use App\Domain\Reports\DTOs\ReportFilters;
use App\Models\Project;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Abstraction over project persistence.
 *
 * The domain depends on this contract rather than Eloquent directly, so the
 * storage mechanism can change without touching business rules or controllers.
 */
interface ProjectRepositoryInterface
{
    /**
     * @return LengthAwarePaginator<int, Project>
     */
    public function paginateForUser(int $userId, int $perPage = 15): LengthAwarePaginator;

    /**
     * @return Collection<int, Project>
     */
    public function reportForUser(int $userId, ReportFilters $filters): Collection;

    public function create(ProjectData $data): Project;

    public function update(Project $project, ProjectData $data): Project;

    public function delete(Project $project): bool;
}
