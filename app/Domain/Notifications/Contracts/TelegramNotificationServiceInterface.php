<?php

declare(strict_types=1);

namespace App\Domain\Notifications\Contracts;

/**
 * Abstraction over the Telegram messaging gateway.
 *
 * The domain depends on this contract rather than the HTTP client directly, so
 * the transport (Telegram Bot API today) can change without touching the rules
 * that decide when a reminder should be delivered.
 */
interface TelegramNotificationServiceInterface
{
    /**
     * Send a text message to a Telegram chat.
     *
     * Implementations must never throw on delivery problems: they return
     * false and log the failure so callers can decide what to do next.
     */
    public function send(?string $chatId, string $message): bool;
}
