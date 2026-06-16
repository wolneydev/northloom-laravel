<?php

declare(strict_types=1);

namespace App\Http\Requests\Task;

use App\Domain\Tasks\DTOs\TaskData;
use App\Http\Requests\Task\Concerns\NormalizesTaskInput;
use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaskRequest extends FormRequest
{
    use NormalizesTaskInput;

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
            'project_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('projects', 'id')->where('user_id', $this->user()->id),
            ],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'task_date' => ['sometimes', 'required', 'date'],
            'starts_at' => ['sometimes', 'required', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'notes' => ['nullable', 'string'],
            'location' => ['nullable', 'string', 'max:255'],
            'priority' => ['nullable', Rule::in(['low', 'medium', 'high'])],
            'status' => ['sometimes', 'required', Rule::in(['pending', 'in_progress', 'completed', 'cancelled'])],
            'notify' => ['sometimes', 'boolean'],
            'notify_minutes_before' => ['nullable', 'integer', 'min:0', 'required_if:notify,true'],
        ];
    }

    /**
     * Normalize time-only schedules and Portuguese labels, then backfill the
     * persisted start when only the end is updated, so the after:starts_at
     * comparison always has both sides available.
     */
    protected function prepareForValidation(): void
    {
        $task = $this->route('task');
        $date = $this->input('task_date')
            ?? ($task instanceof Task ? $task->task_date?->toDateString() : null);

        $merge = [];

        if ($this->has('starts_at')) {
            $merge['starts_at'] = $this->combineDateAndTime($date, $this->input('starts_at'));
        }

        if ($this->has('ends_at')) {
            $merge['ends_at'] = $this->combineDateAndTime($date, $this->input('ends_at'));
        }

        if ($this->has('priority')) {
            $merge['priority'] = $this->normalizePriority($this->input('priority'));
        }

        if ($this->has('status')) {
            $merge['status'] = $this->normalizeStatus($this->input('status'));
        }

        if ($task instanceof Task && $this->has('ends_at') && ! $this->has('starts_at')) {
            $merge['starts_at'] = $task->starts_at?->toDateTimeString();
        }

        if ($merge !== []) {
            $this->merge($merge);
        }
    }

    public function toData(): TaskData
    {
        /** @var array<string, mixed> $validated */
        $validated = $this->validated();

        if ($this->has('notify')) {
            $validated['notify'] = $this->boolean('notify');
        }

        return TaskData::fromArray($validated);
    }
}
