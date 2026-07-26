<?php

declare(strict_types=1);

namespace App\Http\Requests\Financials;

use App\Domain\Financials\DTOs\FinancialAllocationData;
use App\Domain\Financials\ValueObjects\Money;
use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;

final class StoreFinancialAllocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $task = $this->route('task');

        return $task instanceof Task && ($this->user()?->can('view', $task) ?? false);
    }

    public function rules(): array
    {
        return [
            'fund_id' => ['required', 'integer', 'min:1'],
            'amount' => ['required', 'string', 'regex:/^(?:0\.(?:0[1-9]|[1-9]\d)|[1-9]\d{0,12}\.\d{2})$/'],
        ];
    }

    public function toData(): FinancialAllocationData
    {
        /** @var Task $task */
        $task = $this->route('task');

        return new FinancialAllocationData(
            task_id: $task->id,
            fund_id: (int) $this->validated('fund_id'),
            amount: Money::fromString((string) $this->validated('amount'))->toString(),
            actor_user_id: (int) $this->user()->id,
        );
    }
}
