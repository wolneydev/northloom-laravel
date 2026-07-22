<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Tasks\Services\TaskNotificationService;
use Illuminate\Console\Command;

/**
 * Delivers due task reminders over Telegram.
 *
 * Idempotent: safe to run every minute (and after Docker restarts). Pending
 * rows with scheduled_at <= now and sent_at IS NULL are claimed atomically,
 * sent once, and retried with backoff on failure. Business rules live in
 * TaskNotificationService so this command stays a thin entry point.
 */
final class SendTaskNotifications extends Command
{
    protected $signature = 'tasks:send-notifications';

    protected $description = 'Send Telegram reminders for tasks whose notification time has arrived';

    public function handle(TaskNotificationService $notifications): int
    {
        $sent = $notifications->dispatchDueNotifications();

        $this->info("Task notifications sent: {$sent}.");

        return self::SUCCESS;
    }
}
