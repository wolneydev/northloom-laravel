<?php

declare(strict_types=1);

namespace App\Http\Requests\Financials;

use App\Domain\Financials\DTOs\FundData;
use App\Domain\Financials\ValueObjects\Money;
use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;

final class StoreFundRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');

        return $project instanceof Project && ($this->user()?->can('view', $project) ?? false);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'opening_balance' => ['required', 'string', 'regex:/^(?:0|[1-9]\d{0,12})\.\d{2}$/'],
        ];
    }

    public function toData(): FundData
    {
        /** @var Project $project */
        $project = $this->route('project');

        return new FundData(
            project_id: $project->id,
            name: (string) $this->validated('name'),
            opening_balance: Money::fromString((string) $this->validated('opening_balance'))->toString(),
        );
    }
}
