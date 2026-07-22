<?php

declare(strict_types=1);

namespace App\Domain\Tasks\Services;

use App\Domain\Notifications\Contracts\TelegramNotificationServiceInterface;
use App\Domain\Tasks\Repositories\TaskRepositoryInterface;
use App\Models\Task;
use App\Models\User;
use Carbon\CarbonImmutable;

/**
 * Decides which task reminders are due and delivers them through notification
 * channels.
 *
 * The dispatch is idempotent: each reminder is claimed atomically before send,
 * stamped with notification_sent_at only on success, and retried with exponential
 * backoff via next_attempt_at on failure. Overdue rows (scheduled_at /
 * notify_at_datetime <= now with sent_at still null) are always eligible so a
 * downtime or Docker restart recovers missed deliveries without duplicates.
 */
final readonly class TaskNotificationService
{
    /** Hard stop so a permanently failing channel does not retry forever. */
    public const MAX_ATTEMPTS = 8;

    /** Lease window while a send is in flight (minutes). */
    private const CLAIM_LEASE_MINUTES = 5;

    /** Cap for exponential backoff between retries (minutes). */
    private const MAX_BACKOFF_MINUTES = 60;

    public function __construct(
        private TaskRepositoryInterface $tasks,
        private TelegramNotificationServiceInterface $telegram,
    ) {}

    /**
     * @return int Number of reminders successfully delivered in this run.
     */
    public function dispatchDueNotifications(): int
    {
        $now = CarbonImmutable::now();
        $sent = 0;

        foreach ($this->tasks->pendingNotifications($now, self::MAX_ATTEMPTS) as $task) {
            if (! $this->isDue($task, $now)) {
                continue;
            }

            $user = $task->user;

            if ($user === null || ! $user->telegram_notifications_enabled) {
                continue;
            }

            $chatId = $user->telegram_chat_id;

            if ($chatId === null || $chatId === '') {
                continue;
            }

            $leaseUntil = $now->addMinutes(self::CLAIM_LEASE_MINUTES);

            if (! $this->tasks->claimPendingNotification($task, $now, self::MAX_ATTEMPTS, $leaseUntil)) {
                // Another worker claimed or already sent this reminder.
                continue;
            }

            if ($this->deliverNotifications($task, $user)) {
                if ($this->tasks->markNotificationSent($task, $now)) {
                    $sent++;
                }

                continue;
            }

            $this->tasks->scheduleNotificationRetry(
                $task,
                $now->addMinutes($this->backoffMinutes($task->attempts)),
            );
        }

        return $sent;
    }

    /**
     * The reminder is due once the current moment reaches notify_at_datetime
     * (scheduled_at). Past-due rows are intentionally included for recovery.
     */
    private function isDue(Task $task, CarbonImmutable $now): bool
    {
        if ($task->notify_at_datetime === null) {
            return false;
        }

        return $now->greaterThanOrEqualTo($task->notify_at_datetime);
    }

    /**
     * Dispatch the reminder through every configured notification channel.
     *
     * Telegram is the first channel; additional services can be wired here
     * without changing the scheduling rules above.
     */
    private function deliverNotifications(Task $task, User $user): bool
    {
        $message = $this->buildMessage($task);

        return $this->telegram->send($user->telegram_chat_id, $message);
    }

    /**
     * Exponential backoff in minutes: 1, 2, 4, 8… capped at MAX_BACKOFF_MINUTES.
     */
    private function backoffMinutes(int $attempts): int
    {
        $exponent = max($attempts - 1, 0);

        return (int) min(2 ** $exponent, self::MAX_BACKOFF_MINUTES);
    }

    private function buildMessage(Task $task): string
    {
        $projectName = $task->project?->name ?? '-';
        $startsAt = $task->starts_at?->format('d/m/Y H:i') ?? '-';
        $location = $task->location ?? '-';
        $notes = $task->notes ?? '-';

        return <<<MESSAGE
        🔔 Lembrete de tarefa

        Projeto: {$projectName}
        Tarefa: {$task->title}
        Data/Hora: {$startsAt}
        Local: {$location}

        Observações:
        {$notes}
        MESSAGE;
    }
}
