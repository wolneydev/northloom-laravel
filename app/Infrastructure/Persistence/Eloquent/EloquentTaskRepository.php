<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\Tasks\DTOs\TaskData;
use App\Domain\Tasks\DTOs\TaskFilters;
use App\Domain\Tasks\Repositories\TaskRepositoryInterface;
use App\Models\Task;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Eloquent-backed implementation of the task repository contract.
 *
 * This is the only place that knows about Eloquent for tasks, isolating the
 * framework's persistence details from the domain.
 */
final class EloquentTaskRepository implements TaskRepositoryInterface
{
    /**
     * @return LengthAwarePaginator<int, Task>
     */
    public function paginateForUser(int $userId, TaskFilters $filters, int $perPage = 15): LengthAwarePaginator
    {
        return Task::query()
            ->with('project')
            ->where('user_id', $userId)
            ->when($filters->start !== null, fn ($query) => $query->whereDate('task_date', '>=', $filters->start))
            ->when($filters->end !== null, fn ($query) => $query->whereDate('task_date', '<=', $filters->end))
            ->when($filters->project_id !== null, fn ($query) => $query->where('project_id', $filters->project_id))
            ->when($filters->status !== null, fn ($query) => $query->where('status', $filters->status))
            ->when($filters->priority !== null, fn ($query) => $query->where('priority', $filters->priority))
            ->orderBy('task_date')
            ->orderBy('starts_at')
            ->paginate($perPage);
    }

    public function create(TaskData $data): Task
    {
        $task = Task::query()->create($data->toArray());

        return $task->load('project');
    }

    public function update(Task $task, TaskData $data): Task
    {
        $task->fill($data->toArray());
        $task->save();

        return $task->refresh()->load('project');
    }

    public function delete(Task $task): bool
    {
        return (bool) $task->delete();
    }
}
