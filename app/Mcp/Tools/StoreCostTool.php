<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Domain\Financials\DTOs\CostData;
use App\Domain\Financials\Exceptions\ProjectCurrencyNotConfiguredException;
use App\Domain\Financials\Services\CostService;
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
 * Mirrors the validation rules of StoreCostRequest (see
 * app/OpenApi/Schemas.php -> StoreCostRequest).
 */
#[Description('Cria um novo custo (cost) para um projeto do usuário de serviço configurado no MCP (USER_LOGIN/PASSWORD_USER).')]
#[IsIdempotent(false)]
#[IsOpenWorld(false)]
final class StoreCostTool extends Tool
{
    use AuthenticatesMcpUser;

    public function __construct(private readonly CostService $costs) {}

    public function handle(Request $request): Response|ResponseFactory
    {
        $user = $this->resolveActingUser();

        $validated = $request->validate([
            'project_id' => ['required', 'integer', 'min:1'],
            'amount' => ['required', 'string', 'regex:/^(?:0\.(?:0[1-9]|[1-9]\d)|[1-9]\d{0,12}\.\d{2})$/'],
            'description' => ['required', 'string', 'max:1000'],
            'incurred_on' => ['required', 'date_format:Y-m-d'],
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

        $data = new CostData(
            project_id: $project->id,
            amount: Money::fromString((string) $validated['amount'])->toString(),
            description: (string) $validated['description'],
            incurred_on: (string) $validated['incurred_on'],
        );

        try {
            $cost = $this->costs->create($project, $data);
        } catch (ProjectCurrencyNotConfiguredException $exception) {
            throw ValidationException::withMessages([
                'project_id' => [$exception->getMessage()],
            ]);
        }

        return Response::structured([
            'id' => $cost->id,
            'project_id' => $cost->project_id,
            'currency' => $cost->project->currency,
            'amount' => $cost->amount,
            'description' => $cost->description,
            'incurred_on' => $cost->incurred_on?->toDateString(),
            'created_at' => $cost->created_at?->toISOString(),
            'updated_at' => $cost->updated_at?->toISOString(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'project_id' => $schema->integer()
                ->description('ID do projeto ao qual o custo pertence. Deve pertencer ao usuário de serviço do MCP.')
                ->required(),
            'amount' => $schema->string()
                ->description('Valor do custo, formato decimal com 2 casas, ex: "150.00".')
                ->required(),
            'description' => $schema->string()
                ->description('Descrição do custo.')
                ->max(1000)
                ->required(),
            'incurred_on' => $schema->string()
                ->description('Data em que o custo ocorreu, formato YYYY-MM-DD.')
                ->required(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function outputSchema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('ID do custo criado.'),
            'project_id' => $schema->integer(),
            'currency' => $schema->string(),
            'amount' => $schema->string(),
            'description' => $schema->string(),
            'incurred_on' => $schema->string(),
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
            'amount.required' => 'O campo "amount" é obrigatório.',
            'amount.regex' => 'O campo "amount" deve ser um valor decimal positivo com 2 casas, ex: "150.00".',
            'description.required' => 'O campo "description" é obrigatório.',
            'description.max' => 'O campo "description" deve ter no máximo 1000 caracteres.',
            'incurred_on.required' => 'O campo "incurred_on" é obrigatório, no formato YYYY-MM-DD.',
            'incurred_on.date_format' => 'O campo "incurred_on" deve ser uma data válida, no formato YYYY-MM-DD.',
        ];
    }
}
