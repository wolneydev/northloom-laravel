<?php

declare(strict_types=1);

namespace App\Mcp\Concerns;

use App\Models\Task;

/**
 * Mirrors App\Http\Resources\TaskResource for MCP tool structured output.
 */
trait PresentsTaskData
{
    /**
     * @return array<string, mixed>
     */
    private function presentTask(Task $task): array
    {
        return [
            'id' => $task->id,
            'project_id' => $task->project_id,
            'project_name' => $task->relationLoaded('project') ? $task->project?->name : null,
            'title' => $task->title,
            'task_date' => $task->task_date?->toDateString(),
            'starts_at' => $task->starts_at?->toIso8601String(),
            'ends_at' => $task->ends_at?->toIso8601String(),
            'notes' => $task->notes,
            'location' => $task->location,
            'priority' => $task->priority,
            'status' => $task->status,
            'notify' => $task->notify,
            'notify_at_datetime' => $task->notify_at_datetime?->toIso8601String(),
            'notification_sent_at' => $task->notification_sent_at?->toIso8601String(),
        ];
    }
}
