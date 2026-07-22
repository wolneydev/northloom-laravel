<?php

declare(strict_types=1);

namespace App\Http\Requests\Task;

use App\Domain\Tasks\DTOs\TaskData;
use App\Http\Requests\Task\Concerns\NormalizesTaskInput;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaskRequest extends FormRequest
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
                'required',
                'integer',
                // A task may only be attached to a project owned by the caller.
                Rule::exists('projects', 'id')->where('user_id', $this->user()->id),
            ],
            'title' => ['required', 'string', 'max:255'],
            'task_date' => ['required', 'date'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'notes' => ['nullable', 'string'],
            'location' => ['nullable', 'string', 'max:255'],
            'priority' => ['nullable', Rule::in(['low', 'medium', 'high'])],
            'status' => ['nullable', Rule::in(['pending', 'in_progress', 'completed', 'cancelled'])],
            'notify' => ['boolean'],
            'notify_at_datetime' => ['nullable', 'date', 'required_if:notify,true'],
        ];
    }

    /**
     * Accept time-only schedules and Portuguese priority/status labels.
     */
    protected function prepareForValidation(): void
    {
        $date = $this->input('task_date');
        $merge = [];

        if ($this->has('starts_at')) {
            $merge['starts_at'] = $this->combineDateAndTime($date, $this->input('starts_at'));
        }

        if ($this->has('ends_at')) {
            $merge['ends_at'] = $this->combineDateAndTime($date, $this->input('ends_at'));
        }

        if ($this->has('notify_at_datetime')) {
            $merge['notify_at_datetime'] = $this->combineDateAndTime($date, $this->input('notify_at_datetime'));
        }

        if ($this->has('priority')) {
            $merge['priority'] = $this->normalizePriority($this->input('priority'));
        }

        if ($this->has('status')) {
            $merge['status'] = $this->normalizeStatus($this->input('status'));
        }

        if ($merge !== []) {
            $this->merge($merge);
        }
    }

    public function toData(): TaskData
    {
        /** @var array<string, mixed> $validated */
        $validated = $this->validated();
        $validated['user_id'] = $this->user()->id;
        $validated['notify'] = $this->boolean('notify');

        return TaskData::fromArray($validated);
    }
}
