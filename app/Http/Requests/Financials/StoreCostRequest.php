<?php

declare(strict_types=1);

namespace App\Http\Requests\Financials;

use App\Domain\Financials\DTOs\CostData;
use App\Domain\Financials\ValueObjects\Money;
use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;

final class StoreCostRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');

        return $project instanceof Project && ($this->user()?->can('view', $project) ?? false);
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'string', 'regex:/^(?:0\.(?:0[1-9]|[1-9]\d)|[1-9]\d{0,12}\.\d{2})$/'],
            'description' => ['required', 'string', 'max:1000'],
            'incurred_on' => ['required', 'date_format:Y-m-d'],
        ];
    }

    public function toData(): CostData
    {
        /** @var Project $project */
        $project = $this->route('project');

        return new CostData(
            project_id: $project->id,
            amount: Money::fromString((string) $this->validated('amount'))->toString(),
            description: (string) $this->validated('description'),
            incurred_on: (string) $this->validated('incurred_on'),
        );
    }
}
