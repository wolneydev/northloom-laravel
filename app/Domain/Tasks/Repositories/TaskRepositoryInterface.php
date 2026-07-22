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
     * Pending reminders ready for delivery: notifications enabled, not yet sent
     * (sent_at / notification_sent_at IS NULL), scheduled time reached
     * (scheduled_at / notify_at_datetime <= now), under the attempt limit, and
     * either never tried or past next_attempt_at (backoff / lease expired).
     *
     * Includes overdue rows so a restart after downtime still recovers them.
     * Owner and project are eager loaded for message building and Telegram checks.
     *
     * @return Collection<int, Task>
     */
    public function pendingNotifications(DateTimeInterface $now, int $maxAttempts): Collection;

    /**
     * Atomically claim a pending reminder for delivery.
     *
     * Increments attempts and pushes next_attempt_at forward as a short lease so
     * concurrent workers (or a second artisan run) cannot send the same reminder
     * twice. Returns false when another process already claimed or sent it.
     */
    public function claimPendingNotification(Task $task, DateTimeInterface $now, int $maxAttempts, DateTimeInterface $leaseUntil): bool;

    /**
     * Persist the moment a reminder was delivered so it is never sent twice.
     * Only updates when notification_sent_at is still null (idempotent stamp).
     *
     * @return bool True when this process won the stamp; false if already sent.
     */
    public function markNotificationSent(Task $task, DateTimeInterface $sentAt): bool;

    /**
     * Record a failed delivery: schedule the next retry with backoff.
     */
    public function scheduleNotificationRetry(Task $task, DateTimeInterface $nextAttemptAt): Task;
}
