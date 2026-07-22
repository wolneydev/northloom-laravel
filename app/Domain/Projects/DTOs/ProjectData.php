<?php

declare(strict_types=1);

namespace App\Domain\Projects\DTOs;

/**
 * Immutable carrier of project attributes between the HTTP layer and the domain.
 *
 * Keeping this framework-agnostic lets the service and repository depend on a
 * stable contract instead of request shapes or array keys scattered around.
 */
final readonly class ProjectData
{
    public function __construct(
        public ?int $user_id = null,
        public ?string $name = null,
        public ?string $starts_on = null,
        public ?string $expected_ends_on = null,
        public ?string $notes = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            user_id: isset($data['user_id']) ? (int) $data['user_id'] : null,
            name: isset($data['name']) ? (string) $data['name'] : null,
            starts_on: isset($data['starts_on']) ? (string) $data['starts_on'] : null,
            expected_ends_on: isset($data['expected_ends_on']) ? (string) $data['expected_ends_on'] : null,
            notes: isset($data['notes']) ? (string) $data['notes'] : null,
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
                'name' => $this->name,
                'starts_on' => $this->starts_on,
                'expected_ends_on' => $this->expected_ends_on,
                'notes' => $this->notes,
            ],
            static fn (mixed $value): bool => $value !== null,
        );
    }
}
