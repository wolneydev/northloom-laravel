<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Domain\Financials\DTOs\FundData;
use App\Domain\Financials\Exceptions\ProjectCurrencyNotConfiguredException;
use App\Domain\Financials\Services\FundService;
use App\Domain\Financials\ValueObjects\Money;
use App\Mcp\Concerns\AuthenticatesMcpUser;
use App\Models\Project;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;

/**
 * Mirrors the validation rules of StoreFundRequest (see
 * app/OpenApi/Schemas.php -> StoreFundRequest).
 */
#[Description('Cria um novo fundo (fund) para um projeto do usuário de serviço configurado no MCP (USER_LOGIN/PASSWORD_USER).')]
#[IsIdempotent(false)]
#[IsOpenWorld(false)]
final class StoreFundTool extends Tool
{
    use AuthenticatesMcpUser;

    public function __construct(private readonly FundService $funds) {}

    public function handle(Request $request): Response|ResponseFactory
    {
        $user = $this->resolveActingUser();

        $validated = $request->validate([
            'project_id' => ['required', 'integer', 'min:1'],
            'name' => ['required', 'string', 'max:255'],
            'opening_balance' => ['required', 'string', 'regex:/^(?:0|[1-9]\d{0,12})\.\d{2}$/'],
        ], $this->validationMessages());

        $project = Project::query()->find($validated['project_id']);

        if ($project === null) {
            throw ValidationException::withMessages([
                'project_id' => ["Nenhum projeto encontrado com o id [{$validated['project_id']}]."],
            ]);
        }

        if ($project->user_id !== $user->id) {
            throw ValidationException::withMessages([
                'project_id' => ['Este projeto não pertence ao usuário de serviço configurado no MCP.'],
            ]);
        }

        $data = new FundData(
            project_id: $project->id,
            name: (string) $validated['name'],
            opening_balance: Money::fromString((string) $validated['opening_balance'])->toString(),
        );

        try {
            $fund = $this->funds->create($project, $data);
        } catch (ProjectCurrencyNotConfiguredException $exception) {
            throw ValidationException::withMessages([
                'project_id' => [$exception->getMessage()],
            ]);
        }

        return Response::structured([
            'id' => $fund->id,
            'project_id' => $fund->project_id,
            'currency' => $fund->project->currency,
            'name' => $fund->name,
            'opening_balance' => $fund->opening_balance,
            'available_balance' => $fund->available_balance,
            'created_at' => $fund->created_at?->toISOString(),
            'updated_at' => $fund->updated_at?->toISOString(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'project_id' => $schema->integer()
                ->description('ID do projeto ao qual o fundo pertence. Deve pertencer ao usuário de serviço do MCP.')
                ->required(),
            'name' => $schema->string()
                ->description('Nome do fundo.')
                ->max(255)
                ->required(),
            'opening_balance' => $schema->string()
                ->description('Saldo inicial do fundo, formato decimal com 2 casas, ex: "1000.00".')
                ->required(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function outputSchema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('ID do fundo criado.'),
            'project_id' => $schema->integer(),
            'currency' => $schema->string(),
            'name' => $schema->string(),
            'opening_balance' => $schema->string(),
            'available_balance' => $schema->string(),
            'created_at' => $schema->string(),
            'updated_at' => $schema->string(),
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
            'project_id.min' => 'O campo "project_id" deve ser maior que zero.',
            'name.required' => 'O campo "name" é obrigatório.',
            'name.max' => 'O campo "name" deve ter no máximo 255 caracteres.',
            'opening_balance.required' => 'O campo "opening_balance" é obrigatório.',
            'opening_balance.regex' => 'O campo "opening_balance" deve ser um valor decimal com 2 casas, ex: "1000.00".',
        ];
    }
}
