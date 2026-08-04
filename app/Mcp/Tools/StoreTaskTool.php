<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Domain\Tasks\DTOs\TaskData;
use App\Domain\Tasks\Services\TaskService;
use App\Mcp\Concerns\AuthenticatesMcpUser;
use App\Mcp\Concerns\NormalizesMcpTaskInput;
use App\Mcp\Concerns\PresentsTaskData;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;

/**
 * Mirrors the validation rules of StoreTaskRequest (see
 * app/OpenApi/Schemas.php -> StoreTaskRequest).
 */
#[Description('Cria uma nova tarefa de calendário associada a um projeto do usuário de serviço configurado no MCP (USER_LOGIN/PASSWORD_USER).')]
#[IsIdempotent(false)]
#[IsOpenWorld(false)]
final class StoreTaskTool extends Tool
{
    use AuthenticatesMcpUser;
    use NormalizesMcpTaskInput;
    use PresentsTaskData;

    public function __construct(private readonly TaskService $tasks) {}

    public function handle(Request $request): Response|ResponseFactory
    {
        $user = $this->resolveActingUser();

        $this->normalizeTaskRequest($request);

        $validated = $request->validate([
            'project_id' => ['required', 'integer', Rule::exists('projects', 'id')->where('user_id', $user->id)],
            'title' => ['required', 'string', 'max:255'],
            'task_date' => ['required', 'date'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'notes' => ['nullable', 'string'],
            'location' => ['nullable', 'string', 'max:255'],
            'priority' => ['nullable', Rule::in(['low', 'medium', 'high'])],
            'status' => ['nullable', Rule::in(['pending', 'in_progress', 'completed', 'cancelled'])],
            'notify' => ['boolean'],
            'notify_at_datetime' => ['nullable', 'date', 'required_if:notify,true'],
        ], $this->validationMessages());

        $validated['user_id'] = $user->id;
        $validated['notify'] = $request->boolean('notify');

        $task = $this->tasks->create(TaskData::fromArray($validated));

        return Response::structured($this->presentTask($task));
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'project_id' => $schema->integer()
                ->description('ID do projeto ao qual a tarefa pertence. Deve pertencer ao usuário de serviço do MCP.')
                ->required(),
            'title' => $schema->string()
                ->description('Título da tarefa.')
                ->max(255)
                ->required(),
            'task_date' => $schema->string()
                ->description('Data da tarefa, formato YYYY-MM-DD.')
                ->required(),
            'starts_at' => $schema->string()
                ->description('Horário de início: aceita HH:MM (combinado com task_date) ou um datetime completo.')
                ->required(),
            'ends_at' => $schema->string()
                ->description('Horário de término (opcional): HH:MM ou datetime completo, posterior a starts_at.')
                ->nullable(),
            'notes' => $schema->string()
                ->description('Notas opcionais sobre a tarefa.')
                ->nullable(),
            'location' => $schema->string()
                ->description('Local opcional da tarefa.')
                ->max(255)
                ->nullable(),
            'priority' => $schema->string()
                ->description('Prioridade: low, medium, high (ou baixa, media, alta).')
                ->nullable(),
            'status' => $schema->string()
                ->description('Status: pending, in_progress, completed, cancelled (ou variações em português).')
                ->nullable(),
            'notify' => $schema->boolean()
                ->description('Se true, envia notificação no horário definido em notify_at_datetime.'),
            'notify_at_datetime' => $schema->string()
                ->description('Horário da notificação: HH:MM ou datetime completo. Obrigatório quando notify=true.')
                ->nullable(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function outputSchema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('ID da tarefa criada.'),
            'project_id' => $schema->integer(),
            'project_name' => $schema->string()->nullable(),
            'title' => $schema->string(),
            'task_date' => $schema->string(),
            'starts_at' => $schema->string(),
            'ends_at' => $schema->string()->nullable(),
            'notes' => $schema->string()->nullable(),
            'location' => $schema->string()->nullable(),
            'priority' => $schema->string()->nullable(),
            'status' => $schema->string(),
            'notify' => $schema->boolean(),
            'notify_at_datetime' => $schema->string()->nullable(),
            'notification_sent_at' => $schema->string()->nullable(),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function validationMessages(): array
    {
        return [
            'project_id.required' => 'O campo "project_id" é obrigatório.',
            'project_id.integer' => 'O campo "project_id" deve ser um número inteiro.',
            'project_id.exists' => 'Nenhum projeto encontrado com esse "project_id" pertencente ao usuário de serviço do MCP.',
            'title.required' => 'O campo "title" é obrigatório.',
            'title.max' => 'O campo "title" deve ter no máximo 255 caracteres.',
            'task_date.required' => 'O campo "task_date" é obrigatório, no formato YYYY-MM-DD.',
            'task_date.date' => 'O campo "task_date" deve ser uma data válida, no formato YYYY-MM-DD.',
            'starts_at.required' => 'O campo "starts_at" é obrigatório (HH:MM ou datetime completo).',
            'starts_at.date' => 'O campo "starts_at" deve ser um horário/data válido.',
            'ends_at.date' => 'O campo "ends_at" deve ser um horário/data válido.',
            'ends_at.after' => 'O campo "ends_at" deve ser posterior a "starts_at".',
            'notes.string' => 'O campo "notes" deve ser um texto.',
            'location.max' => 'O campo "location" deve ter no máximo 255 caracteres.',
            'priority.in' => 'O campo "priority" deve ser low, medium ou high.',
            'status.in' => 'O campo "status" deve ser pending, in_progress, completed ou cancelled.',
            'notify.boolean' => 'O campo "notify" deve ser verdadeiro ou falso.',
            'notify_at_datetime.date' => 'O campo "notify_at_datetime" deve ser um horário/data válido.',
            'notify_at_datetime.required_if' => 'O campo "notify_at_datetime" é obrigatório quando "notify" é true.',
        ];
    }
}
