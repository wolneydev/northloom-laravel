<?php

declare(strict_types=1);

namespace App\Domain\CreationIdeas\DTOs;

/**
 * Symbolic cycle snapshot used to pick a headline and suggestion.
 */
final readonly class CreationIdeaContext
{
    public function __construct(
        public string $country,
        public string $timezone,
        public string $local_date_time,
        public string $day_period,
        public string $season,
        public string $weekday,
        public int $task_count_on_day,
        public int $upcoming_task_count,
        public ?string $lunar_phase,
        public string $primary_mode,
        public string $secondary_mode,
    ) {}
}
