<?php

declare(strict_types=1);

namespace App\Domain\Projects\Services;

use App\Domain\Projects\DTOs\ProjectData;
use App\Domain\Projects\Repositories\ProjectRepositoryInterface;
use App\Models\Project;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Holds the business rules for managing projects.
 *
 * Controllers stay thin by delegating here; data access stays behind the
 * repository contract. This keeps the rules testable in isolation.
 */
final readonly class ProjectService
{
    public function __construct(
        private ProjectRepositoryInterface $projects,
    ) {}

    /**
     * @return LengthAwarePaginator<int, Project>
     */
    public function listForUser(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->projects->paginateForUser($userId, $perPage);
    }

    public function create(ProjectData $data): Project
    {
        return $this->projects->create($data);
    }

    public function update(Project $project, ProjectData $data): Project
    {
        return $this->projects->update($project, $data);
    }

    public function delete(Project $project): bool
    {
        return $this->projects->delete($project);
    }
}
