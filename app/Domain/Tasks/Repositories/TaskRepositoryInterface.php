<?php

declare(strict_types=1);

namespace App\Domain\Tasks\Repositories;

use App\Domain\Reports\DTOs\ReportFilters;
use App\Domain\Tasks\DTOs\TaskData;
use App\Domain\Tasks\DTOs\TaskFilters;
use App\Models\Task;
use DateTimeInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Abstraction over task persistence.
 *
 * The domain depends on this contract rather than Eloquent directly, so the
 * storage mechanism can change without touching business rules or controllers.
 */
interface TaskRepositoryInterface
{
    /**
     * @return LengthAwarePaginator<int, Task>
     */
    public function paginateForUser(int $userId, TaskFilters $filters, int $perPage = 15): LengthAwarePaginator;

    /**
     * @return Collection<int, Task>
     */
    public function reportForUser(int $userId, ReportFilters $filters): Collection;

    public function create(TaskData $data): Task;

    public function update(Task $task, TaskData $data): Task;

    public function delete(Task $task): bool;

    /**
     * Tasks that still need a reminder: notifications enabled, not yet sent and
     * scheduled to start at or after the given moment. The owner and project
     * relations are eager loaded so callers can build the message and check the
     * user's Telegram preferences without extra queries.
     *
     * @return Collection<int, Task>
     */
    public function pendingNotifications(DateTimeInterface $now): Collection;

    /**
     * Persist the moment a reminder was delivered so it is never sent twice.
     */
    public function markNotificationSent(Task $task, DateTimeInterface $sentAt): Task;
}
