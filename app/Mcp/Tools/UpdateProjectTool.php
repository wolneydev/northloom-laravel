<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Domain\Projects\DTOs\ProjectData;
use App\Domain\Projects\Exceptions\ProjectCurrencyImmutableException;
use App\Domain\Projects\Services\ProjectService;
use App\Mcp\Concerns\AuthenticatesMcpUser;
use App\Models\Project;
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
 * Mirrors the validation rules of UpdateProjectRequest (see
 * app/OpenApi/Schemas.php -> UpdateProjectRequest).
 */
#[Description('Atualiza um projeto existente pertencente ao usuário de serviço configurado no MCP (USER_LOGIN/PASSWORD_USER).')]
#[IsIdempotent(true)]
#[IsOpenWorld(false)]
final class UpdateProjectTool extends Tool
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
            'id' => ['required', 'integer', 'min:1'],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'currency' => ['sometimes', 'required', 'string', 'size:3', Rule::in(config('financial.currencies'))],
            'starts_on' => ['sometimes', 'required', 'date'],
            'expected_ends_on' => ['sometimes', 'required', 'date', 'after_or_equal:starts_on'],
            'notes' => ['nullable', 'string'],
        ], $this->validationMessages());

        $project = Project::query()->find($validated['id']);

        if ($project === null) {
            throw ValidationException::withMessages([
                'id' => ["Nenhum projeto encontrado com o id [{$validated['id']}]."],
            ]);
        }

        if ($project->user_id !== $user->id) {
            throw ValidationException::withMessages([
                'id' => ['Este projeto não pertence ao usuário de serviço configurado no MCP.'],
            ]);
        }

        unset($validated['id']);

        // Backfill the persisted date when only one side changes, so
        // after_or_equal always has both values to compare (mirrors
        // UpdateProjectRequest::prepareForValidation).
        if (array_key_exists('starts_on', $validated) && ! array_key_exists('expected_ends_on', $validated)) {
            $validated['expected_ends_on'] = $project->expected_ends_on?->toDateString();
        }

        if (array_key_exists('expected_ends_on', $validated) && ! array_key_exists('starts_on', $validated)) {
            $validated['starts_on'] = $project->starts_on?->toDateString();
        }

        try {
            $updated = $this->projects->update($project, ProjectData::fromArray($validated));
        } catch (ProjectCurrencyImmutableException $exception) {
            throw ValidationException::withMessages([
                'currency' => [$exception->getMessage()],
            ]);
        }

        return Response::structured([
            'id' => $updated->id,
            'name' => $updated->name,
            'currency' => $updated->currency,
            'starts_on' => $updated->starts_on?->toDateString(),
            'expected_ends_on' => $updated->expected_ends_on?->toDateString(),
            'notes' => $updated->notes,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()
                ->description('ID do projeto a ser atualizado.')
                ->required(),
            'name' => $schema->string()
                ->description('Novo nome do projeto.')
                ->max(255),
            'currency' => $schema->string()
                ->description('Novo código ISO da moeda (3 letras). Não pode mudar depois que o projeto tiver funds ou costs registrados.')
                ->min(3)
                ->max(3),
            'starts_on' => $schema->string()
                ->description('Nova data de início, formato YYYY-MM-DD.'),
            'expected_ends_on' => $schema->string()
                ->description('Nova data prevista de término, formato YYYY-MM-DD.'),
            'notes' => $schema->string()
                ->description('Novas notas do projeto.')
                ->nullable(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function outputSchema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('ID do projeto atualizado.'),
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
            'id.required' => 'O campo "id" é obrigatório.',
            'id.integer' => 'O campo "id" deve ser um número inteiro.',
            'id.min' => 'O campo "id" deve ser maior que zero.',
            'name.required' => 'O campo "name" é obrigatório quando enviado.',
            'name.max' => 'O campo "name" deve ter no máximo 255 caracteres.',
            'currency.required' => 'O campo "currency" é obrigatório quando enviado.',
            'currency.size' => 'O campo "currency" deve ter exatamente 3 letras (código ISO), ex: BRL.',
            'currency.in' => 'O campo "currency" deve ser uma das moedas configuradas: '.implode(', ', config('financial.currencies')).'.',
            'starts_on.required' => 'O campo "starts_on" é obrigatório quando enviado, no formato YYYY-MM-DD.',
            'starts_on.date' => 'O campo "starts_on" deve ser uma data válida, no formato YYYY-MM-DD.',
            'expected_ends_on.required' => 'O campo "expected_ends_on" é obrigatório quando enviado, no formato YYYY-MM-DD.',
            'expected_ends_on.date' => 'O campo "expected_ends_on" deve ser uma data válida, no formato YYYY-MM-DD.',
            'expected_ends_on.after_or_equal' => 'O campo "expected_ends_on" deve ser igual ou posterior a "starts_on".',
            'notes.string' => 'O campo "notes" deve ser um texto.',
        ];
    }
}
