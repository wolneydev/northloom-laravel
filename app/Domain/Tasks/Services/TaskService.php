<?php

declare(strict_types=1);

namespace App\Domain\Tasks\Services;

use App\Domain\Tasks\DTOs\TaskData;
use App\Domain\Tasks\DTOs\TaskFilters;
use App\Domain\Tasks\Repositories\TaskRepositoryInterface;
use App\Models\Task;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Holds the business rules for managing calendar tasks.
 *
 * Controllers stay thin by delegating here; data access stays behind the
 * repository contract. This keeps the rules testable in isolation.
 */
final readonly class TaskService
{
    public function __construct(
        private TaskRepositoryInterface $tasks,
    ) {}

    /**
     * @return LengthAwarePaginator<int, Task>
     */
    public function listForUser(int $userId, TaskFilters $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->tasks->paginateForUser($userId, $filters, $perPage);
    }

    public function create(TaskData $data): Task
    {
        return $this->tasks->create($data);
    }

    public function update(Task $task, TaskData $data): Task
    {
        return $this->tasks->update($task, $data);
    }

    public function delete(Task $task): bool
    {
        return $this->tasks->delete($task);
    }
}
