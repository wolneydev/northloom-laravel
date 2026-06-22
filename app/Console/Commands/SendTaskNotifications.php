<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Tasks\Services\TaskNotificationService;
use Illuminate\Console\Command;

/**
 * Delivers due task reminders over Telegram.
 *
 * Meant to run every minute via the scheduler; the business rules live in
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
