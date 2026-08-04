<?php

declare(strict_types=1);

namespace App\Http\Requests\Project;

use App\Domain\Projects\DTOs\ProjectData;
use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProjectRequest extends FormRequest
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
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'currency' => ['sometimes', 'required', 'string', 'size:3', Rule::in(config('financial.currencies'))],
            'starts_on' => ['sometimes', 'required', 'date'],
            'expected_ends_on' => ['sometimes', 'required', 'date', 'after_or_equal:starts_on'],
            'notes' => ['nullable', 'string'],
        ];
    }

    /**
     * Backfill the persisted dates when only one of them is updated, so the
     * after_or_equal comparison always has both sides available.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('currency') && is_string($this->input('currency'))) {
            $this->merge(['currency' => strtoupper(trim($this->string('currency')->toString()))]);
        }

        $project = $this->route('project');

        if (! $project instanceof Project) {
            return;
        }

        $hasStart = $this->has('starts_on');
        $hasEnd = $this->has('expected_ends_on');

        if ($hasStart && ! $hasEnd) {
            $this->merge(['expected_ends_on' => $project->expected_ends_on?->toDateString()]);
        }

        if ($hasEnd && ! $hasStart) {
            $this->merge(['starts_on' => $project->starts_on?->toDateString()]);
        }
    }

    public function toData(): ProjectData
    {
        /** @var array<string, mixed> $validated */
        $validated = $this->validated();

        return ProjectData::fromArray($validated);
    }
}
