<?php

declare(strict_types=1);

namespace App\Domain\Tasks\DTOs;

/**
 * Immutable carrier of task attributes between the HTTP layer and the domain.
 *
 * Keeping this framework-agnostic lets the service and repository depend on a
 * stable contract instead of request shapes or array keys scattered around.
 */
final readonly class TaskData
{
    public function __construct(
        public ?int $user_id = null,
        public ?int $project_id = null,
        public ?string $title = null,
        public ?string $task_date = null,
        public ?string $starts_at = null,
        public ?string $ends_at = null,
        public ?string $notes = null,
        public ?string $location = null,
        public ?string $priority = null,
        public ?string $status = null,
        public ?bool $notify = null,
        public ?int $notify_minutes_before = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            user_id: isset($data['user_id']) ? (int) $data['user_id'] : null,
            project_id: isset($data['project_id']) ? (int) $data['project_id'] : null,
            title: isset($data['title']) ? (string) $data['title'] : null,
            task_date: isset($data['task_date']) ? (string) $data['task_date'] : null,
            starts_at: isset($data['starts_at']) ? (string) $data['starts_at'] : null,
            ends_at: isset($data['ends_at']) ? (string) $data['ends_at'] : null,
            notes: isset($data['notes']) ? (string) $data['notes'] : null,
            location: isset($data['location']) ? (string) $data['location'] : null,
            priority: isset($data['priority']) ? (string) $data['priority'] : null,
            status: isset($data['status']) ? (string) $data['status'] : null,
            notify: array_key_exists('notify', $data) ? (bool) $data['notify'] : null,
            notify_minutes_before: isset($data['notify_minutes_before']) ? (int) $data['notify_minutes_before'] : null,
        );
    }

    /**
     * Only the provided attributes are returned, so the same DTO works for
     * full creation and partial updates without overwriting untouched columns.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter(
            [
                'user_id' => $this->user_id,
                'project_id' => $this->project_id,
                'title' => $this->title,
                'task_date' => $this->task_date,
                'starts_at' => $this->starts_at,
                'ends_at' => $this->ends_at,
                'notes' => $this->notes,
                'location' => $this->location,
                'priority' => $this->priority,
                'status' => $this->status,
                'notify' => $this->notify,
                'notify_minutes_before' => $this->notify_minutes_before,
            ],
            static fn (mixed $value): bool => $value !== null,
        );
    }
}
