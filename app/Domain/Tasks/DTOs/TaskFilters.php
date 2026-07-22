<?php

declare(strict_types=1);

namespace App\Domain\Tasks\DTOs;

/**
 * Optional criteria used to narrow a calendar task listing.
 */
final readonly class TaskFilters
{
    public function __construct(
        public ?string $start = null,
        public ?string $end = null,
        public ?int $project_id = null,
        public ?string $status = null,
        public ?string $priority = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            start: isset($data['start']) ? (string) $data['start'] : null,
            end: isset($data['end']) ? (string) $data['end'] : null,
            project_id: isset($data['project_id']) ? (int) $data['project_id'] : null,
            status: isset($data['status']) ? (string) $data['status'] : null,
            priority: isset($data['priority']) ? (string) $data['priority'] : null,
        );
    }
}
