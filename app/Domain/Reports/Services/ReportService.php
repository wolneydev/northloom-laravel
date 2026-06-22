<?php

declare(strict_types=1);

namespace App\Domain\Reports\Services;

use App\Domain\Projects\Repositories\ProjectRepositoryInterface;
use App\Domain\Reports\DTOs\ReportFilters;
use App\Domain\Tasks\Repositories\TaskRepositoryInterface;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Support\Collection;

/**
 * Builds the project/task report data for the authenticated user.
 */
final class ReportService
{
    public function __construct(
        private readonly ProjectRepositoryInterface $projects,
        private readonly TaskRepositoryInterface $tasks,
    ) {}

    /**
     * @return array{
     *     projects?: Collection<int, Project>,
     *     tasks?: Collection<int, Task>
     * }
     */
    public function buildForUser(int $userId, ReportFilters $filters): array
    {
        $report = [];

        if ($filters->includesProjects()) {
            $report['projects'] = $this->projects->reportForUser($userId, $filters);
        }

        if ($filters->includesTasks()) {
            $report['tasks'] = $this->tasks->reportForUser($userId, $filters);
        }

        return $report;
    }
}
