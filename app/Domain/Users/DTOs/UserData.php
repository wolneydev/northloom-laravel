<?php

declare(strict_types=1);

namespace App\Domain\Users\DTOs;

/**
 * Immutable carrier of user attributes between the HTTP layer and the domain.
 *
 * Keeping this framework-agnostic lets the service and repository depend on a
 * stable contract instead of request shapes or array keys scattered around.
 */
final readonly class UserData
{
    public function __construct(
        public ?string $name = null,
        public ?string $email = null,
        public ?string $password = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: isset($data['name']) ? (string) $data['name'] : null,
            email: isset($data['email']) ? (string) $data['email'] : null,
            password: isset($data['password']) ? (string) $data['password'] : null,
        );
    }

    /**
     * Only the provided attributes are returned, so the same DTO works for
     * full creation and partial updates without overwriting untouched columns.
     *
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return array_filter(
            [
                'name' => $this->name,
                'email' => $this->email,
                'password' => $this->password,
            ],
            static fn (?string $value): bool => $value !== null,
        );
    }
}
