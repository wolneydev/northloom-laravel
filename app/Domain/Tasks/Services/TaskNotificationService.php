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
 * Keeping this rule in the domain (instead of the console command) makes it
 * testable in isolation and reusable from other entry points. A task is only
 * notified when notify_at_datetime has arrived, the owner has Telegram
 * notifications enabled with a chat id, and the delivery succeeds — at which
 * point it is marked as sent so it never fires twice.
 */
final readonly class TaskNotificationService
{
    public function __construct(
        private TaskRepositoryInterface $tasks,
        private TelegramNotificationServiceInterface $telegram,
    ) {}

    /**
     * @return int Number of reminders successfully delivered.
     */
    public function dispatchDueNotifications(): int
    {
        $now = CarbonImmutable::now();
        $sent = 0;

        foreach ($this->tasks->pendingNotifications($now) as $task) {
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

            if ($this->deliverNotifications($task, $user)) {
                $this->tasks->markNotificationSent($task, $now);
                $sent++;
            }
        }

        return $sent;
    }

    /**
     * The reminder is due once the current moment reaches notify_at_datetime.
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
