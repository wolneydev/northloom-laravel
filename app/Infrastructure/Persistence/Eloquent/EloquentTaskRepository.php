<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\Tasks\DTOs\TaskData;
use App\Domain\Tasks\DTOs\TaskFilters;
use App\Domain\Tasks\Repositories\TaskRepositoryInterface;
use App\Models\Task;
use DateTimeInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

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

    /**
     * @return Collection<int, Task>
     */
    public function pendingNotifications(DateTimeInterface $now): Collection
    {
        // The exact "notify_at_datetime <= now" comparison is done in PHP by
        // the domain service: it keeps the logic database-agnostic and easy to
        // test. Here we only narrow down to tasks still awaiting a reminder so
        // the working set stays small.
        return Task::query()
            ->with(['user', 'project'])
            ->where('notify', true)
            ->whereNull('notification_sent_at')
            ->whereNotNull('notify_at_datetime')
            ->where('notify_at_datetime', '<=', $now)
            ->orderBy('notify_at_datetime')
            ->get();
    }

    public function markNotificationSent(Task $task, DateTimeInterface $sentAt): Task
    {
        $task->forceFill(['notification_sent_at' => $sentAt])->save();

        return $task;
    }
}
