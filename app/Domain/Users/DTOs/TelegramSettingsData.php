<?php

declare(strict_types=1);

namespace App\Domain\Users\DTOs;

/**
 * Immutable carrier of a user's Telegram preferences.
 *
 * Unlike UserData this never filters out values, so a chat id can be cleared
 * (null) and notifications can be explicitly disabled (false) without the
 * change being silently dropped.
 */
final readonly class TelegramSettingsData
{
    public function __construct(
        public ?string $telegram_chat_id,
        public bool $telegram_notifications_enabled,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $chatId = $data['telegram_chat_id'] ?? null;

        return new self(
            telegram_chat_id: $chatId === null ? null : (string) $chatId,
            telegram_notifications_enabled: (bool) ($data['telegram_notifications_enabled'] ?? false),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'telegram_chat_id' => $this->telegram_chat_id,
            'telegram_notifications_enabled' => $this->telegram_notifications_enabled,
        ];
    }
}
