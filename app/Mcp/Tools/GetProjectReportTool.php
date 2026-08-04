<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Domain\Reports\DTOs\ReportFilters;
use App\Domain\Reports\Services\ReportService;
use App\Mcp\Concerns\AuthenticatesMcpUser;
use App\Mcp\Concerns\PresentsTaskData;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

/**
 * Mirrors the validation rules of ShowReportRequest / ReportFilters (see
 * app/OpenApi/Schemas.php -> ReportFilters).
 */
#[Description('Gera um relatório agregado de projetos e/ou tarefas do usuário de serviço configurado no MCP (USER_LOGIN/PASSWORD_USER).')]
#[IsReadOnly(true)]
#[IsIdempotent(true)]
#[IsOpenWorld(false)]
final class GetProjectReportTool extends Tool
{
    use AuthenticatesMcpUser;
    use PresentsTaskData;

    public function __construct(private readonly ReportService $reports) {}

    public function handle(Request $request): Response|ResponseFactory
    {
        $user = $this->resolveActingUser();

        $validated = $request->validate([
            'report_type' => ['required', Rule::in(['projects', 'tasks', 'both'])],
            'status' => ['nullable', Rule::in(['pending', 'in_progress', 'completed', 'cancelled'])],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ], $this->validationMessages());

        $filters = ReportFilters::fromArray($validated);
        $report = $this->reports->buildForUser($user->id, $filters);

        $data = [
            'filters' => [
                'report_type' => $filters->report_type,
                'status' => $filters->status,
                'start_date' => $filters->start_date,
                'end_date' => $filters->end_date,
            ],
        ];

        if (isset($report['projects'])) {
            /** @var Collection<int, Project> $projects */
            $projects = $report['projects'];
            $data['projects'] = $projects->map(fn (Project $project): array => $this->presentProjectReport($project))->values()->all();
        }

        if (isset($report['tasks'])) {
            /** @var Collection<int, Task> $tasks */
            $tasks = $report['tasks'];
            $data['tasks'] = $tasks->map(fn (Task $task): array => $this->presentTask($task))->values()->all();
        }

        return Response::structured($data);
    }

    /**
     * Mirrors App\Http\Resources\ProjectReportResource.
     *
     * @return array<string, mixed>
     */
    private function presentProjectReport(Project $project): array
    {
        return [
            'id' => $project->id,
            'name' => $project->name,
            'currency' => $project->currency,
            'starts_on' => $project->starts_on?->toDateString(),
            'expected_ends_on' => $project->expected_ends_on?->toDateString(),
            'notes' => $project->notes,
            'tasks_count' => (int) $project->getAttribute('tasks_count'),
            'status_counts' => [
                'pending' => (int) $project->getAttribute('pending_tasks_count'),
                'in_progress' => (int) $project->getAttribute('in_progress_tasks_count'),
                'completed' => (int) $project->getAttribute('completed_tasks_count'),
                'cancelled' => (int) $project->getAttribute('cancelled_tasks_count'),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'report_type' => $schema->string()
                ->description('Tipo de relatório: projects, tasks ou both.')
                ->required(),
            'status' => $schema->string()
                ->description('Filtra por status: pending, in_progress, completed ou cancelled.')
                ->nullable(),
            'start_date' => $schema->string()
                ->description('Data inicial do filtro, formato YYYY-MM-DD.')
                ->nullable(),
            'end_date' => $schema->string()
                ->description('Data final do filtro, formato YYYY-MM-DD. Deve ser igual ou posterior a start_date.')
                ->nullable(),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function validationMessages(): array
    {
        return [
            'report_type.required' => 'O campo "report_type" é obrigatório.',
            'report_type.in' => 'O campo "report_type" deve ser projects, tasks ou both.',
            'status.in' => 'O campo "status" deve ser pending, in_progress, completed ou cancelled.',
            'start_date.date' => 'O campo "start_date" deve ser uma data válida, no formato YYYY-MM-DD.',
            'end_date.date' => 'O campo "end_date" deve ser uma data válida, no formato YYYY-MM-DD.',
            'end_date.after_or_equal' => 'O campo "end_date" deve ser igual ou posterior a "start_date".',
        ];
    }
}
