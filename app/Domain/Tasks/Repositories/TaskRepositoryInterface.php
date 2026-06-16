<?php

declare(strict_types=1);

namespace App\Domain\Tasks\Repositories;

use App\Domain\Tasks\DTOs\TaskData;
use App\Domain\Tasks\DTOs\TaskFilters;
use App\Models\Task;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

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

    public function create(TaskData $data): Task;

    public function update(Task $task, TaskData $data): Task;

    public function delete(Task $task): bool;
}
