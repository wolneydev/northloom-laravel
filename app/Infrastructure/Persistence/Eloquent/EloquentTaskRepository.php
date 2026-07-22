<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\Reports\DTOs\ReportFilters;
use App\Domain\Tasks\DTOs\TaskData;
use App\Domain\Tasks\DTOs\TaskFilters;
use App\Domain\Tasks\Repositories\TaskRepositoryInterface;
use App\Models\Task;
use DateTimeInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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

    /**
     * @return Collection<int, Task>
     */
    public function reportForUser(int $userId, ReportFilters $filters): Collection
    {
        return Task::query()
            ->with('project')
            ->where('user_id', $userId)
            ->tap(fn (Builder $query) => $this->applyReportFilters($query, $filters))
            ->orderByDesc('task_date')
            ->orderByDesc('starts_at')
            ->get();
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
    public function pendingNotifications(DateTimeInterface $now, int $maxAttempts): Collection
    {
        // scheduled_at (notify_at_datetime) <= now AND sent_at (notification_sent_at)
        // IS NULL, plus backoff / attempt filters. Overdue rows are included so
        // downtime (e.g. Docker restart) is recovered on the next run.
        return Task::query()
            ->with(['user', 'project'])
            ->where('notify', true)
            ->whereNull('notification_sent_at')
            ->whereNotNull('notify_at_datetime')
            ->where('notify_at_datetime', '<=', $now)
            ->where('attempts', '<', $maxAttempts)
            ->where(function (Builder $query) use ($now): void {
                $query->whereNull('next_attempt_at')
                    ->orWhere('next_attempt_at', '<=', $now);
            })
            ->orderBy('notify_at_datetime')
            ->get();
    }

    public function claimPendingNotification(
        Task $task,
        DateTimeInterface $now,
        int $maxAttempts,
        DateTimeInterface $leaseUntil,
    ): bool {
        $affected = Task::query()
            ->whereKey($task->id)
            ->where('notify', true)
            ->whereNull('notification_sent_at')
            ->whereNotNull('notify_at_datetime')
            ->where('notify_at_datetime', '<=', $now)
            ->where('attempts', '<', $maxAttempts)
            ->where(function (Builder $query) use ($now): void {
                $query->whereNull('next_attempt_at')
                    ->orWhere('next_attempt_at', '<=', $now);
            })
            ->update([
                'attempts' => DB::raw('attempts + 1'),
                'next_attempt_at' => $leaseUntil,
            ]);

        if ($affected !== 1) {
            return false;
        }

        $task->refresh();

        return true;
    }

    public function markNotificationSent(Task $task, DateTimeInterface $sentAt): bool
    {
        $affected = Task::query()
            ->whereKey($task->id)
            ->whereNull('notification_sent_at')
            ->update(['notification_sent_at' => $sentAt]);

        if ($affected === 1) {
            $task->forceFill(['notification_sent_at' => $sentAt]);
        }

        return $affected === 1;
    }

    public function scheduleNotificationRetry(Task $task, DateTimeInterface $nextAttemptAt): Task
    {
        $task->forceFill(['next_attempt_at' => $nextAttemptAt])->save();

        return $task;
    }

    private function applyReportFilters(Builder $query, ReportFilters $filters): Builder
    {
        return $query
            ->when($filters->start_date !== null, fn (Builder $query) => $query->whereDate('task_date', '>=', $filters->start_date))
            ->when($filters->end_date !== null, fn (Builder $query) => $query->whereDate('task_date', '<=', $filters->end_date))
            ->when($filters->status !== null, fn (Builder $query) => $query->where('status', $filters->status));
    }
}
