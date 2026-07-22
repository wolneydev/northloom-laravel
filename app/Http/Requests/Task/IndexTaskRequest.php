<?php

declare(strict_types=1);

namespace App\Http\Requests\Task;

use App\Domain\Tasks\DTOs\TaskFilters;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexTaskRequest extends FormRequest
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
            'start' => ['nullable', 'date'],
            'end' => ['nullable', 'date', 'after_or_equal:start'],
            'project_id' => ['nullable', 'integer'],
            'status' => ['nullable', Rule::in(['pending', 'in_progress', 'completed', 'cancelled'])],
            'priority' => ['nullable', Rule::in(['low', 'medium', 'high'])],
        ];
    }

    public function toFilters(): TaskFilters
    {
        /** @var array<string, mixed> $validated */
        $validated = $this->validated();

        return TaskFilters::fromArray($validated);
    }
}
