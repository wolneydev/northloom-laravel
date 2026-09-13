<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\CreationIdeas;

use App\Domain\CreationIdeas\DTOs\CreationIdeaQuery;
use App\Domain\CreationIdeas\Services\CreationIdeaCopyGenerator;
use App\Domain\CreationIdeas\Services\CreationIdeaCycleMapper;
use App\Domain\CreationIdeas\Services\CreationIdeaService;
use App\Domain\Tasks\Repositories\TaskRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery;
use RuntimeException;
use Tests\TestCase;

final class CreationIdeaServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_sunday_night_spring_returns_exact_growth_copy(): void
    {
        $idea = $this->serviceWithEmptyCalendar()->suggestForUser(1, new CreationIdeaQuery(
            target: 'project',
            at: '2026-09-13T21:30:00-03:00',
        ));

        $this->assertSame('project', $idea->target);
        $this->assertSame('Protótipo de uma ideia ainda não explorada', $idea->headline);
        $this->assertSame(
            'Use este momento para crescimento e imaginação. Crie um projeto em torno de uma ideia que você ainda não testou, com o objetivo de validar uma versão pequena na semana que começa.',
            $idea->suggestion,
        );
        $this->assertSame('name', $idea->field_hints['headline']);
        $this->assertSame('notes', $idea->field_hints['suggestion']);
        $this->assertSame('night', $idea->context->day_period);
        $this->assertSame('spring', $idea->context->season);
        $this->assertSame('sunday', $idea->context->weekday);
        $this->assertSame('growth', $idea->context->primary_mode);
        $this->assertSame('imagination', $idea->context->secondary_mode);
        $this->assertNull($idea->context->lunar_phase);
        $this->assertSame(0, $idea->context->task_count_on_day);
        $this->assertLessThanOrEqual(255, mb_strlen($idea->headline));
    }

    public function test_monday_morning_winter_returns_week_priority_copy(): void
    {
        $idea = $this->serviceWithEmptyCalendar()->suggestForUser(1, new CreationIdeaQuery(
            target: 'project',
            at: '2026-06-15T08:00:00-03:00',
        ));

        $this->assertSame('Prioridades da semana que começa', $idea->headline);
        $this->assertSame('morning', $idea->context->day_period);
        $this->assertSame('winter', $idea->context->season);
        $this->assertSame('planning', $idea->context->primary_mode);
        $this->assertSame('planning', $idea->context->secondary_mode);
    }

    public function test_context_detects_night_after_midnight(): void
    {
        $idea = $this->serviceWithEmptyCalendar()->suggestForUser(1, new CreationIdeaQuery(
            target: 'project',
            at: '2026-09-14T03:00:00-03:00',
        ));

        $this->assertSame('night', $idea->context->day_period);
        $this->assertSame('imagination', $idea->context->secondary_mode);
    }

    public function test_calendar_load_failure_is_treated_as_empty(): void
    {
        $tasks = Mockery::mock(TaskRepositoryInterface::class);
        $tasks->shouldReceive('paginateForUser')->andThrow(new RuntimeException('unavailable'));

        $idea = (new CreationIdeaService(
            $tasks,
            new CreationIdeaCycleMapper,
            new CreationIdeaCopyGenerator,
        ))->suggestForUser(7, new CreationIdeaQuery(
            target: 'project',
            at: '2026-09-13T21:30:00-03:00',
        ));

        $this->assertSame(0, $idea->context->task_count_on_day);
        $this->assertSame(0, $idea->context->upcoming_task_count);
        $this->assertNotSame('', $idea->suggestion);
    }

    public function test_task_target_maps_headline_to_title(): void
    {
        $idea = $this->serviceWithEmptyCalendar()->suggestForUser(1, new CreationIdeaQuery(
            target: 'task',
            project_id: 42,
            at: '2026-01-14T15:00:00-03:00',
        ));

        $this->assertSame('task', $idea->target);
        $this->assertSame('title', $idea->field_hints['headline']);
        $this->assertSame('Próxima ação de maior impacto', $idea->headline);
    }

    private function serviceWithEmptyCalendar(): CreationIdeaService
    {
        $tasks = Mockery::mock(TaskRepositoryInterface::class);
        $tasks->shouldReceive('paginateForUser')->andReturn(new LengthAwarePaginator([], 0, 1));

        return new CreationIdeaService(
            $tasks,
            new CreationIdeaCycleMapper,
            new CreationIdeaCopyGenerator,
        );
    }
}
