<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Project
 */
class ProjectReportResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'starts_on' => $this->starts_on?->toDateString(),
            'expected_ends_on' => $this->expected_ends_on?->toDateString(),
            'notes' => $this->notes,
            'tasks_count' => (int) $this->resource->getAttribute('tasks_count'),
            'status_counts' => [
                'pending' => (int) $this->resource->getAttribute('pending_tasks_count'),
                'in_progress' => (int) $this->resource->getAttribute('in_progress_tasks_count'),
                'completed' => (int) $this->resource->getAttribute('completed_tasks_count'),
                'cancelled' => (int) $this->resource->getAttribute('cancelled_tasks_count'),
            ],
        ];
    }
}
