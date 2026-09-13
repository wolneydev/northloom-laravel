<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\CreationIdeas\DTOs\CreationIdea;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin CreationIdea
 */
class CreationIdeaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var CreationIdea $idea */
        $idea = $this->resource;

        return [
            'target' => $idea->target,
            'headline' => $idea->headline,
            'suggestion' => $idea->suggestion,
            'field_hints' => $idea->field_hints,
            'context' => [
                'country' => $idea->context->country,
                'timezone' => $idea->context->timezone,
                'local_date_time' => $idea->context->local_date_time,
                'day_period' => $idea->context->day_period,
                'season' => $idea->context->season,
                'weekday' => $idea->context->weekday,
                'task_count_on_day' => $idea->context->task_count_on_day,
                'upcoming_task_count' => $idea->context->upcoming_task_count,
                'lunar_phase' => $idea->context->lunar_phase,
                'primary_mode' => $idea->context->primary_mode,
                'secondary_mode' => $idea->context->secondary_mode,
            ],
        ];
    }
}
