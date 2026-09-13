<?php

declare(strict_types=1);

namespace App\Domain\CreationIdeas\Services;

use App\Domain\CreationIdeas\DTOs\CreationIdea;
use App\Domain\CreationIdeas\DTOs\CreationIdeaQuery;
use App\Domain\Tasks\DTOs\TaskFilters;
use App\Domain\Tasks\Repositories\TaskRepositoryInterface;
use Illuminate\Support\Carbon;
use Throwable;

/**
 * Builds a non-persisting project/task suggestion from local time and the user's calendar.
 */
final readonly class CreationIdeaService
{
    public function __construct(
        private TaskRepositoryInterface $tasks,
        private CreationIdeaCycleMapper $cycle,
        private CreationIdeaCopyGenerator $copy,
    ) {}

    public function suggestForUser(int $userId, CreationIdeaQuery $query): CreationIdea
    {
        $timezone = (string) config('app.timezone');
        $at = $query->at !== null
            ? Carbon::parse($query->at)->timezone($timezone)
            : now($timezone);

        $counts = $this->calendarCounts($userId, $at);
        $context = $this->cycle->build(
            $at,
            $counts['task_count_on_day'],
            $counts['upcoming_task_count'],
        );
        $copy = $this->copy->generate($query->target, $context);

        return new CreationIdea(
            target: $query->target,
            headline: $copy['headline'],
            suggestion: $copy['suggestion'],
            field_hints: $query->target === 'task'
                ? ['headline' => 'title', 'suggestion' => 'notes']
                : ['headline' => 'name', 'suggestion' => 'notes'],
            context: $context,
        );
    }

    /**
     * @return array{task_count_on_day: int, upcoming_task_count: int}
     */
    private function calendarCounts(int $userId, Carbon $at): array
    {
        $day = $at->toDateString();
        $endOfWeek = $at->copy()->endOfWeek()->toDateString();

        try {
            return [
                'task_count_on_day' => $this->countTasks($userId, $day, $day),
                'upcoming_task_count' => $this->countTasks($userId, $day, $endOfWeek),
            ];
        } catch (Throwable) {
            return [
                'task_count_on_day' => 0,
                'upcoming_task_count' => 0,
            ];
        }
    }

    private function countTasks(int $userId, string $start, string $end): int
    {
        return $this->tasks->paginateForUser(
            $userId,
            TaskFilters::fromArray([
                'start' => $start,
                'end' => $end,
            ]),
            1,
        )->total();
    }
}
