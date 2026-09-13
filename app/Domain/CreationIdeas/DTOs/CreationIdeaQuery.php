<?php

declare(strict_types=1);

namespace App\Domain\CreationIdeas\DTOs;

/**
 * Query accepted by the creation-idea endpoint and MCP tool.
 */
final readonly class CreationIdeaQuery
{
    public function __construct(
        public string $target,
        public ?int $project_id = null,
        public ?string $at = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            target: (string) $data['target'],
            project_id: isset($data['project_id']) ? (int) $data['project_id'] : null,
            at: isset($data['at']) ? (string) $data['at'] : null,
        );
    }
}
