<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Domain\Projects\DTOs\ProjectData;
use App\Domain\Projects\Services\ProjectService;
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

/**
 * Mirrors the validation rules of StoreProjectRequest (see
 * app/OpenApi/Schemas.php -> StoreProjectRequest).
 */
#[Description('Cria um novo projeto associado ao usuário de serviço configurado no MCP (USER_LOGIN/PASSWORD_USER).')]
#[IsIdempotent(false)]
#[IsOpenWorld(false)]
final class StoreProjectTool extends Tool
{
    use AuthenticatesMcpUser;

    public function __construct(private readonly ProjectService $projects) {}

    public function handle(Request $request): Response|ResponseFactory
    {
        $user = $this->resolveActingUser();

        if ($request->get('currency') !== null) {
            $request->merge(['currency' => strtoupper(trim((string) $request->get('currency')))]);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'currency' => ['required', 'string', 'size:3', Rule::in(config('financial.currencies'))],
            'starts_on' => ['required', 'date'],
            'expected_ends_on' => ['required', 'date', 'after_or_equal:starts_on'],
            'notes' => ['nullable', 'string'],
        ], $this->validationMessages());

        $validated['user_id'] = $user->id;

        $project = $this->projects->create(ProjectData::fromArray($validated));

        return Response::structured([
            'id' => $project->id,
            'name' => $project->name,
            'currency' => $project->currency,
            'starts_on' => $project->starts_on?->toDateString(),
            'expected_ends_on' => $project->expected_ends_on?->toDateString(),
            'notes' => $project->notes,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()
                ->description('Nome do projeto.')
                ->max(255)
                ->required(),
            'currency' => $schema->string()
                ->description('Código ISO da moeda (3 letras), ex: BRL. Deve estar entre as moedas configuradas em financial.currencies.')
                ->min(3)
                ->max(3)
                ->required(),
            'starts_on' => $schema->string()
                ->description('Data de início do projeto, formato YYYY-MM-DD.')
                ->required(),
            'expected_ends_on' => $schema->string()
                ->description('Data prevista de término, formato YYYY-MM-DD. Deve ser igual ou posterior a starts_on.')
                ->required(),
            'notes' => $schema->string()
                ->description('Notas opcionais sobre o projeto.')
                ->nullable(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function outputSchema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('ID do projeto criado.'),
            'name' => $schema->string(),
            'currency' => $schema->string(),
            'starts_on' => $schema->string(),
            'expected_ends_on' => $schema->string(),
            'notes' => $schema->string()->nullable(),
        ];
    }

    /**
     * Explicit messages so the LLM gets readable feedback even in
     * environments without the app's validation language files published.
     *
     * @return array<string, string>
     */
    private function validationMessages(): array
    {
        return [
            'name.required' => 'O campo "name" é obrigatório.',
            'name.max' => 'O campo "name" deve ter no máximo 255 caracteres.',
            'currency.required' => 'O campo "currency" é obrigatório.',
            'currency.size' => 'O campo "currency" deve ter exatamente 3 letras (código ISO), ex: BRL.',
            'currency.in' => 'O campo "currency" deve ser uma das moedas configuradas: '.implode(', ', config('financial.currencies')).'.',
            'starts_on.required' => 'O campo "starts_on" é obrigatório, no formato YYYY-MM-DD.',
            'starts_on.date' => 'O campo "starts_on" deve ser uma data válida, no formato YYYY-MM-DD.',
            'expected_ends_on.required' => 'O campo "expected_ends_on" é obrigatório, no formato YYYY-MM-DD.',
            'expected_ends_on.date' => 'O campo "expected_ends_on" deve ser uma data válida, no formato YYYY-MM-DD.',
            'expected_ends_on.after_or_equal' => 'O campo "expected_ends_on" deve ser igual ou posterior a "starts_on".',
            'notes.string' => 'O campo "notes" deve ser um texto.',
        ];
    }
}
