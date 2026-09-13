<?php

declare(strict_types=1);

namespace App\Domain\CreationIdeas\DTOs;

/**
 * Read-only suggestion the client may paste into store payloads.
 */
final readonly class CreationIdea
{
    /**
     * @param  array{headline: string, suggestion: string}  $field_hints
     */
    public function __construct(
        public string $target,
        public string $headline,
        public string $suggestion,
        public array $field_hints,
        public CreationIdeaContext $context,
    ) {}
}
