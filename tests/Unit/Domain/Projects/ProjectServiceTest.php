<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Projects;

use App\Domain\Projects\DTOs\ProjectData;
use App\Domain\Projects\Exceptions\ProjectCurrencyImmutableException;
use App\Domain\Projects\Repositories\ProjectRepositoryInterface;
use App\Domain\Projects\Services\ProjectService;
use App\Models\Project;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Mockery;
use PHPUnit\Framework\TestCase;

final class ProjectServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_legacy_project_can_receive_its_initial_currency(): void
    {
        $project = new Project(['currency' => null]);
        $data = new ProjectData(currency: 'USD');
        $projects = Mockery::mock(ProjectRepositoryInterface::class);
        $projects->shouldReceive('update')->once()->with($project, $data)->andReturn($project);

        self::assertSame($project, (new ProjectService($projects))->update($project, $data));
    }

    public function test_currency_change_is_rejected_after_financial_activity(): void
    {
        $funds = Mockery::mock(HasMany::class);
        $funds->shouldReceive('exists')->once()->andReturnTrue();

        $project = Mockery::mock(Project::class)->makePartial();
        $project->currency = 'BRL';
        $project->shouldReceive('funds')->once()->andReturn($funds);

        $projects = Mockery::mock(ProjectRepositoryInterface::class);
        $projects->shouldNotReceive('update');

        $this->expectException(ProjectCurrencyImmutableException::class);

        (new ProjectService($projects))->update($project, new ProjectData(currency: 'USD'));
    }
}
