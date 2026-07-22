<?php

declare(strict_types=1);

namespace App\Http\Requests\Report;

use App\Domain\Reports\DTOs\ReportFilters;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ShowReportRequest extends FormRequest
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
            'report_type' => ['required', Rule::in(['projects', 'tasks', 'both'])],
            'status' => ['nullable', Rule::in(['pending', 'in_progress', 'completed', 'cancelled'])],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ];
    }

    public function toFilters(): ReportFilters
    {
        /** @var array<string, mixed> $validated */
        $validated = $this->validated();

        return ReportFilters::fromArray($validated);
    }
}
