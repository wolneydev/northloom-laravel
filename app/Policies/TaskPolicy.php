<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

/**
 * Ensures a task can only be reached by its owner.
 */
class TaskPolicy
{
    public function view(User $user, Task $task): bool
    {
        return $this->owns($user, $task);
    }

    public function update(User $user, Task $task): bool
    {
        return $this->owns($user, $task);
    }

    public function delete(User $user, Task $task): bool
    {
        return $this->owns($user, $task);
    }

    private function owns(User $user, Task $task): bool
    {
        return $user->id === $task->user_id;
    }
}
