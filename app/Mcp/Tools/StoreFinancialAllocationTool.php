<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Domain\Financials\DTOs\FinancialAllocationData;
use App\Domain\Financials\Exceptions\FinancialOwnershipException;
use App\Domain\Financials\Exceptions\FundProjectMismatchException;
use App\Domain\Financials\Exceptions\InsufficientFundBalanceException;
use App\Domain\Financials\Exceptions\ProjectCurrencyNotConfiguredException;
use App\Domain\Financials\Services\FinancialAllocationService;
use App\Domain\Financials\ValueObjects\Money;
use App\Mcp\Concerns\AuthenticatesMcpUser;
use App\Models\Task;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;

/**
 * Mirrors the validation rules of StoreFinancialAllocationRequest (see
 * app/OpenApi/Schemas.php -> StoreFinancialAllocationRequest).
 */
#[Description('Aloca dinheiro de um fundo (fund) para uma tarefa, em nome do usuário de serviço configurado no MCP (USER_LOGIN/PASSWORD_USER).')]
#[IsIdempotent(false)]
#[IsOpenWorld(false)]
final class StoreFinancialAllocationTool extends Tool
{
    use AuthenticatesMcpUser;

    public function __construct(private readonly FinancialAllocationService $allocations) {}

    public function handle(Request $request): Response|ResponseFactory
    {
        $user = $this->resolveActingUser();

        $validated = $request->validate([
            'task_id' => ['required', 'integer', 'min:1'],
            'fund_id' => ['required', 'integer', 'min:1'],
            'amount' => ['required', 'string', 'regex:/^(?:0\.(?:0[1-9]|[1-9]\d)|[1-9]\d{0,12}\.\d{2})$/'],
        ], $this->validationMessages());

        $task = Task::query()->find($validated['task_id']);

        if ($task === null) {
            throw ValidationException::withMessages([
                'task_id' => ["Nenhuma tarefa encontrada com o id [{$validated['task_id']}]."],
            ]);
        }

        if ($task->user_id !== $user->id) {
            throw ValidationException::withMessages([
                'task_id' => ['Esta tarefa não pertence ao usuário de serviço configurado no MCP.'],
            ]);
        }

        $data = new FinancialAllocationData(
            task_id: $task->id,
            fund_id: (int) $validated['fund_id'],
            amount: Money::fromString((string) $validated['amount'])->toString(),
            actor_user_id: $user->id,
        );

        try {
            $allocation = $this->allocations->allocate($task, $data);
        } catch (ModelNotFoundException) {
            throw ValidationException::withMessages([
                'fund_id' => ["Nenhum fundo encontrado com o id [{$validated['fund_id']}]."],
            ]);
        } catch (FinancialOwnershipException|FundProjectMismatchException $exception) {
            throw ValidationException::withMessages([
                'fund_id' => [$exception->getMessage()],
            ]);
        } catch (InsufficientFundBalanceException $exception) {
            throw ValidationException::withMessages([
                'amount' => [$exception->getMessage()],
            ]);
        } catch (ProjectCurrencyNotConfiguredException $exception) {
            throw ValidationException::withMessages([
                'fund_id' => [$exception->getMessage()],
            ]);
        }

        return Response::structured([
            'id' => $allocation->id,
            'task_id' => $allocation->task_id,
            'fund_id' => $allocation->fund_id,
            'currency' => $allocation->fund->project->currency,
            'amount' => $allocation->amount,
            'allocated_at' => $allocation->allocated_at?->toISOString(),
            'created_at' => $allocation->created_at?->toISOString(),
            'updated_at' => $allocation->updated_at?->toISOString(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'task_id' => $schema->integer()
                ->description('ID da tarefa que receberá a alocação. Deve pertencer ao usuário de serviço do MCP.')
                ->required(),
            'fund_id' => $schema->integer()
                ->description('ID do fundo de onde o dinheiro será retirado. Deve pertencer ao mesmo projeto da tarefa.')
                ->required(),
            'amount' => $schema->string()
                ->description('Valor a ser alocado, formato decimal com 2 casas, ex: "200.00". Não pode exceder o saldo disponível do fundo.')
                ->required(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function outputSchema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('ID da alocação criada.'),
            'task_id' => $schema->integer(),
            'fund_id' => $schema->integer(),
            'currency' => $schema->string(),
            'amount' => $schema->string(),
            'allocated_at' => $schema->string(),
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
            'task_id.required' => 'O campo "task_id" é obrigatório.',
            'task_id.integer' => 'O campo "task_id" deve ser um número inteiro.',
            'task_id.min' => 'O campo "task_id" deve ser maior que zero.',
            'fund_id.required' => 'O campo "fund_id" é obrigatório.',
            'fund_id.integer' => 'O campo "fund_id" deve ser um número inteiro.',
            'fund_id.min' => 'O campo "fund_id" deve ser maior que zero.',
            'amount.required' => 'O campo "amount" é obrigatório.',
            'amount.regex' => 'O campo "amount" deve ser um valor decimal positivo com 2 casas, ex: "200.00".',
        ];
    }
}
