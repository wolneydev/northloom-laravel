<?php

declare(strict_types=1);

namespace App\Mcp\Concerns;

use App\Http\Requests\Task\Concerns\NormalizesTaskInput;
use App\Models\Task;
use Laravel\Mcp\Request;

/**
 * Mirrors StoreTaskRequest/UpdateTaskRequest::prepareForValidation() for the
 * MCP task tools: combines time-only starts_at/ends_at/notify_at_datetime
 * with task_date, maps Portuguese priority/status aliases, and (on update)
 * backfills starts_at from the persisted task when only ends_at changes.
 */
trait NormalizesMcpTaskInput
{
    use NormalizesTaskInput;

    private function normalizeTaskRequest(Request $request, ?Task $existingTask = null): void
    {
        $date = $this->stringOrNull($request->get('task_date'))
            ?? $existingTask?->task_date?->toDateString();

        $merge = [];

        foreach (['starts_at', 'ends_at', 'notify_at_datetime'] as $field) {
            $value = $this->stringOrNull($request->get($field));

            if ($value !== null) {
                $merge[$field] = $this->combineDateAndTime($date, $value);
            }
        }

        $priority = $this->stringOrNull($request->get('priority'));

        if ($priority !== null) {
            $merge['priority'] = $this->normalizePriority($priority);
        }

        $status = $this->stringOrNull($request->get('status'));

        if ($status !== null) {
            $merge['status'] = $this->normalizeStatus($status);
        }

        if ($existingTask instanceof Task && $request->get('ends_at') !== null && $request->get('starts_at') === null) {
            $merge['starts_at'] = $existingTask->starts_at?->toDateTimeString();
        }

        if ($merge !== []) {
            $request->merge($merge);
        }
    }

    private function stringOrNull(mixed $value): ?string
    {
        return $value === null ? null : (string) $value;
    }
}
