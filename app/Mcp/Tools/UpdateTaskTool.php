<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Domain\Tasks\DTOs\TaskData;
use App\Domain\Tasks\Services\TaskService;
use App\Mcp\Concerns\AuthenticatesMcpUser;
use App\Mcp\Concerns\NormalizesMcpTaskInput;
use App\Mcp\Concerns\PresentsTaskData;
use App\Models\Task;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;

/**
 * Mirrors the validation rules of UpdateTaskRequest (see
 * app/OpenApi/Schemas.php -> UpdateTaskRequest).
 */
#[Description('Atualiza uma tarefa existente pertencente ao usuário de serviço configurado no MCP (USER_LOGIN/PASSWORD_USER).')]
#[IsIdempotent(true)]
#[IsOpenWorld(false)]
final class UpdateTaskTool extends Tool
{
    use AuthenticatesMcpUser;
    use NormalizesMcpTaskInput;
    use PresentsTaskData;

    public function __construct(private readonly TaskService $tasks) {}

    public function handle(Request $request): Response|ResponseFactory
    {
        $user = $this->resolveActingUser();

        $id = $request->get('id');

        if (! is_numeric($id)) {
            throw ValidationException::withMessages([
                'id' => ['O campo "id" é obrigatório e deve ser um número inteiro.'],
            ]);
        }

        $task = Task::query()->with('project')->find((int) $id);

        if ($task === null) {
            throw ValidationException::withMessages([
                'id' => ["Nenhuma tarefa encontrada com o id [{$id}]."],
            ]);
        }

        if ($task->user_id !== $user->id) {
            throw ValidationException::withMessages([
                'id' => ['Esta tarefa não pertence ao usuário de serviço configurado no MCP.'],
            ]);
        }

        $this->normalizeTaskRequest($request, $task);

        $validated = $request->validate([
            'project_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('projects', 'id')->where('user_id', $user->id),
            ],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'task_date' => ['sometimes', 'required', 'date'],
            'starts_at' => ['sometimes', 'required', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'notes' => ['nullable', 'string'],
            'location' => ['nullable', 'string', 'max:255'],
            'priority' => ['nullable', Rule::in(['low', 'medium', 'high'])],
            'status' => ['sometimes', 'required', Rule::in(['pending', 'in_progress', 'completed', 'cancelled'])],
            'notify' => ['sometimes', 'boolean'],
            'notify_at_datetime' => ['nullable', 'date', 'required_if:notify,true'],
        ], $this->validationMessages());

        if ($request->get('notify') !== null) {
            $validated['notify'] = $request->boolean('notify');
        }

        $updated = $this->tasks->update($task, TaskData::fromArray($validated));

        return Response::structured($this->presentTask($updated));
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()
                ->description('ID da tarefa a ser atualizada.')
                ->required(),
            'project_id' => $schema->integer()
                ->description('Novo projeto da tarefa. Deve pertencer ao usuário de serviço do MCP.'),
            'title' => $schema->string()
                ->description('Novo título da tarefa.')
                ->max(255),
            'task_date' => $schema->string()
                ->description('Nova data da tarefa, formato YYYY-MM-DD.'),
            'starts_at' => $schema->string()
                ->description('Novo horário de início: HH:MM ou datetime completo.'),
            'ends_at' => $schema->string()
                ->description('Novo horário de término: HH:MM ou datetime completo, posterior a starts_at.')
                ->nullable(),
            'notes' => $schema->string()
                ->description('Novas notas da tarefa.')
                ->nullable(),
            'location' => $schema->string()
                ->description('Novo local da tarefa.')
                ->max(255)
                ->nullable(),
            'priority' => $schema->string()
                ->description('Nova prioridade: low, medium, high (ou baixa, media, alta).')
                ->nullable(),
            'status' => $schema->string()
                ->description('Novo status: pending, in_progress, completed, cancelled (ou variações em português).'),
            'notify' => $schema->boolean()
                ->description('Se true, envia notificação no horário definido em notify_at_datetime.'),
            'notify_at_datetime' => $schema->string()
                ->description('Novo horário da notificação: HH:MM ou datetime completo. Obrigatório quando notify=true.')
                ->nullable(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function outputSchema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('ID da tarefa atualizada.'),
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
            'project_id.required' => 'O campo "project_id" é obrigatório quando enviado.',
            'project_id.integer' => 'O campo "project_id" deve ser um número inteiro.',
            'project_id.exists' => 'Nenhum projeto encontrado com esse "project_id" pertencente ao usuário de serviço do MCP.',
            'title.required' => 'O campo "title" é obrigatório quando enviado.',
            'title.max' => 'O campo "title" deve ter no máximo 255 caracteres.',
            'task_date.required' => 'O campo "task_date" é obrigatório quando enviado, no formato YYYY-MM-DD.',
            'task_date.date' => 'O campo "task_date" deve ser uma data válida, no formato YYYY-MM-DD.',
            'starts_at.required' => 'O campo "starts_at" é obrigatório quando enviado (HH:MM ou datetime completo).',
            'starts_at.date' => 'O campo "starts_at" deve ser um horário/data válido.',
            'ends_at.date' => 'O campo "ends_at" deve ser um horário/data válido.',
            'ends_at.after' => 'O campo "ends_at" deve ser posterior a "starts_at".',
            'notes.string' => 'O campo "notes" deve ser um texto.',
            'location.max' => 'O campo "location" deve ter no máximo 255 caracteres.',
            'priority.in' => 'O campo "priority" deve ser low, medium ou high.',
            'status.required' => 'O campo "status" é obrigatório quando enviado.',
            'status.in' => 'O campo "status" deve ser pending, in_progress, completed ou cancelled.',
            'notify.boolean' => 'O campo "notify" deve ser verdadeiro ou falso.',
            'notify_at_datetime.date' => 'O campo "notify_at_datetime" deve ser um horário/data válido.',
            'notify_at_datetime.required_if' => 'O campo "notify_at_datetime" é obrigatório quando "notify" é true.',
        ];
    }
}
