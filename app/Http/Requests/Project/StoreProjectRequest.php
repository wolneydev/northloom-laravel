<?php

declare(strict_types=1);

namespace App\Http\Requests\Project;

use App\Domain\Projects\DTOs\ProjectData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'currency' => ['nullable', 'string', 'size:3', Rule::in(config('financial.currencies'))],
            'hours' => ['nullable', 'numeric', 'min:0', 'decimal:0,2', 'max:999999.99'],
            'starts_on' => ['required', 'date'],
            'expected_ends_on' => ['required', 'date', 'after_or_equal:starts_on'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function toData(): ProjectData
    {
        /** @var array<string, mixed> $validated */
        $validated = $this->validated();
        $validated['user_id'] = $this->user()->id;

        return ProjectData::fromArray($validated);
    }

    protected function prepareForValidation(): void
    {
        ProjectAttributePreparer::merge($this);
    }
}
