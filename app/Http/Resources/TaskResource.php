<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Task
 */
class TaskResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'project_name' => $this->whenLoaded('project', fn () => $this->project->name),
            'title' => $this->title,
            'task_date' => $this->task_date?->toDateString(),
            'starts_at' => $this->starts_at?->toIso8601ZuluString(),
            'ends_at' => $this->ends_at?->toIso8601ZuluString(),
            'notes' => $this->notes,
            'location' => $this->location,
            'priority' => $this->priority,
            'status' => $this->status,
            'notify' => $this->notify,
            'notify_minutes_before' => $this->notify_minutes_before,
        ];
    }
}
