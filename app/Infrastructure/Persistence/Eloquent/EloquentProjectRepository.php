<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\Projects\DTOs\ProjectData;
use App\Domain\Projects\Repositories\ProjectRepositoryInterface;
use App\Domain\Reports\DTOs\ReportFilters;
use App\Models\Project;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

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

    /**
     * @return Collection<int, Project>
     */
    public function reportForUser(int $userId, ReportFilters $filters): Collection
    {
        return Project::query()
            ->where('user_id', $userId)
            ->when($filters->start_date !== null, fn (Builder $query) => $query->whereDate('expected_ends_on', '>=', $filters->start_date))
            ->when($filters->end_date !== null, fn (Builder $query) => $query->whereDate('starts_on', '<=', $filters->end_date))
            ->when($filters->status !== null, function (Builder $query) use ($filters): void {
                $query->whereHas('tasks', fn (Builder $taskQuery) => $this->applyTaskFilters($taskQuery, $filters));
            })
            ->withCount([
                'tasks as tasks_count' => fn (Builder $query) => $this->applyTaskFilters($query, $filters),
                'tasks as pending_tasks_count' => fn (Builder $query) => $this->applyTaskDateFilters($query, $filters)->where('status', 'pending'),
                'tasks as in_progress_tasks_count' => fn (Builder $query) => $this->applyTaskDateFilters($query, $filters)->where('status', 'in_progress'),
                'tasks as completed_tasks_count' => fn (Builder $query) => $this->applyTaskDateFilters($query, $filters)->where('status', 'completed'),
                'tasks as cancelled_tasks_count' => fn (Builder $query) => $this->applyTaskDateFilters($query, $filters)->where('status', 'cancelled'),
            ])
            ->orderByDesc('expected_ends_on')
            ->orderByDesc('starts_on')
            ->orderBy('name')
            ->get();
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

    private function applyTaskFilters(Builder $query, ReportFilters $filters): Builder
    {
        return $this->applyTaskDateFilters($query, $filters)
            ->when($filters->status !== null, fn (Builder $query) => $query->where('status', $filters->status));
    }

    private function applyTaskDateFilters(Builder $query, ReportFilters $filters): Builder
    {
        return $query
            ->when($filters->start_date !== null, fn (Builder $query) => $query->whereDate('task_date', '>=', $filters->start_date))
            ->when($filters->end_date !== null, fn (Builder $query) => $query->whereDate('task_date', '<=', $filters->end_date));
    }
}
