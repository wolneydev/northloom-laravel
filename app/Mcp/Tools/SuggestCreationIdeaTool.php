<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Domain\CreationIdeas\DTOs\CreationIdeaQuery;
use App\Domain\CreationIdeas\Services\CreationIdeaService;
use App\Mcp\Concerns\AuthenticatesMcpUser;
use Illuminate\Contracts\JsonSchema\JsonSchema;
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
 * Mirrors GET /api/creation-ideas. Does not create projects or tasks.
 */
#[Description('Sugere um título e um texto curto para um projeto ou tarefa com base no horário local do Brasil e na agenda do usuário. Não cria nem altera projetos ou tarefas.')]
#[IsReadOnly(true)]
#[IsIdempotent(true)]
#[IsOpenWorld(false)]
final class SuggestCreationIdeaTool extends Tool
{
    use AuthenticatesMcpUser;

    public function __construct(private readonly CreationIdeaService $ideas) {}

    public function handle(Request $request): Response|ResponseFactory
    {
        $user = $this->resolveActingUser();

        $validated = $request->validate([
            'target' => ['required', Rule::in(['project', 'task'])],
            'project_id' => [
                'required_if:target,task',
                'nullable',
                'integer',
                Rule::exists('projects', 'id')->where('user_id', $user->id),
            ],
            'at' => ['nullable', 'date'],
        ], $this->validationMessages());

        $idea = $this->ideas->suggestForUser($user->id, CreationIdeaQuery::fromArray($validated));

        return Response::structured([
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
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'target' => $schema->string()
                ->description('Destino da sugestão: project ou task.')
                ->required(),
            'project_id' => $schema->integer()
                ->description('Obrigatório quando target é task. Deve pertencer ao usuário de serviço do MCP.')
                ->nullable(),
            'at' => $schema->string()
                ->description('Data e hora opcional para o ciclo simbólico (ISO 8601). Sem este campo, usa o horário atual em America/Sao_Paulo.')
                ->nullable(),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function validationMessages(): array
    {
        return [
            'target.required' => 'O campo "target" é obrigatório.',
            'target.in' => 'O campo "target" deve ser project ou task.',
            'project_id.required_if' => 'O campo "project_id" é obrigatório quando target é task.',
            'project_id.integer' => 'O campo "project_id" deve ser um número inteiro.',
            'project_id.exists' => 'O projeto informado não existe ou não pertence ao usuário de serviço do MCP.',
            'at.date' => 'O campo "at" deve ser uma data e hora válidas.',
        ];
    }
}
