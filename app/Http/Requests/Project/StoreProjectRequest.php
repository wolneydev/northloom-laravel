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
            'currency' => ['required', 'string', 'size:3', Rule::in(config('financial.currencies'))],
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
        if ($this->has('currency') && is_string($this->input('currency'))) {
            $this->merge(['currency' => strtoupper(trim($this->string('currency')->toString()))]);
        }
    }
}
