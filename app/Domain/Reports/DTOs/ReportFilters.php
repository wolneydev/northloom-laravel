<?php

declare(strict_types=1);

namespace App\Domain\Reports\DTOs;

/**
 * Filters accepted by the project/task report endpoint.
 */
final readonly class ReportFilters
{
    public function __construct(
        public string $report_type = 'both',
        public ?string $status = null,
        public ?string $start_date = null,
        public ?string $end_date = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            report_type: isset($data['report_type']) ? (string) $data['report_type'] : 'both',
            status: isset($data['status']) ? (string) $data['status'] : null,
            start_date: isset($data['start_date']) ? (string) $data['start_date'] : null,
            end_date: isset($data['end_date']) ? (string) $data['end_date'] : null,
        );
    }

    public function includesProjects(): bool
    {
        return $this->report_type === 'projects' || $this->report_type === 'both';
    }

    public function includesTasks(): bool
    {
        return $this->report_type === 'tasks' || $this->report_type === 'both';
    }
}
