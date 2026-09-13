<?php

declare(strict_types=1);

namespace App\Domain\CreationIdeas\Services;

use App\Domain\CreationIdeas\DTOs\CreationIdeaContext;
use Carbon\CarbonInterface;
use Illuminate\Support\Str;

/**
 * Maps Brazil-local time onto the symbolic success-cycle (not scientific astrology).
 */
final readonly class CreationIdeaCycleMapper
{
    /**
     * @var array<string, string>
     */
    private const PRIMARY_MODES = [
        'spring' => 'growth',
        'summer' => 'action',
        'autumn' => 'reflection',
        'winter' => 'planning',
    ];

    /**
     * @var array<string, string>
     */
    private const SECONDARY_MODES = [
        'morning' => 'planning',
        'afternoon' => 'execution',
        'night' => 'imagination',
    ];

    public function build(CarbonInterface $at, int $taskCountOnDay, int $upcomingTaskCount): CreationIdeaContext
    {
        $dayPeriod = $this->dayPeriod($at);
        $season = $this->season($at);

        return new CreationIdeaContext(
            country: (string) config('creation_idea.country'),
            timezone: (string) config('app.timezone'),
            local_date_time: $at->format('Y-m-d\TH:i:sP'),
            day_period: $dayPeriod,
            season: $season,
            weekday: Str::lower($at->englishDayOfWeek),
            task_count_on_day: $taskCountOnDay,
            upcoming_task_count: $upcomingTaskCount,
            lunar_phase: null,
            primary_mode: self::PRIMARY_MODES[$season],
            secondary_mode: self::SECONDARY_MODES[$dayPeriod],
        );
    }

    private function dayPeriod(CarbonInterface $at): string
    {
        $time = $at->format('H:i');

        /** @var array<string, array{start: string, end: string}> $periods */
        $periods = config('creation_idea.day_periods');

        foreach (['morning', 'afternoon'] as $name) {
            $window = $periods[$name];

            if ($time >= $window['start'] && $time <= $window['end']) {
                return $name;
            }
        }

        return 'night';
    }

    private function season(CarbonInterface $at): string
    {
        /** @var array<int, string> $seasons */
        $seasons = config('creation_idea.southern_hemisphere_seasons');

        return $seasons[(int) $at->format('n')];
    }
}
