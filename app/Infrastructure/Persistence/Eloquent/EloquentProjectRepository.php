<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\Projects\DTOs\ProjectData;
use App\Domain\Projects\Repositories\ProjectRepositoryInterface;
use App\Models\Project;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Eloquent-backed implementation of the project repository contract.
 *
 * This is the only place that knows about Eloquent for projects, isolating the
 * framework's persistence details from the domain.
 */
final class EloquentProjectRepository implements ProjectRepositoryInterface
{
    /**
     * @return LengthAwarePaginator<int, Project>
     */
    public function paginateForUser(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return Project::query()
            ->where('user_id', $userId)
            ->latest()
            ->paginate($perPage);
    }

    public function create(ProjectData $data): Project
    {
        return Project::query()->create($data->toArray());
    }

    public function update(Project $project, ProjectData $data): Project
    {
        $project->fill($data->toArray());
        $project->save();

        return $project->refresh();
    }

    public function delete(Project $project): bool
    {
        return (bool) $project->delete();
    }
}
