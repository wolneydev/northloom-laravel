<?php

declare(strict_types=1);

namespace App\Infrastructure\Telegram;

use App\Domain\Notifications\Contracts\TelegramNotificationServiceInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Sends messages through the Telegram Bot API using the Laravel HTTP client.
 *
 * This is the only place that knows about Telegram's HTTP transport, isolating
 * the integration details from the domain. Failures are logged and reported as
 * a boolean so callers can keep their own state (e.g. notification_sent_at)
 * consistent.
 */
final class TelegramNotificationService implements TelegramNotificationServiceInterface
{
    private const ENDPOINT = 'https://api.telegram.org/bot%s/sendMessage';

    public function send(?string $chatId, string $message): bool
    {
        $token = config('services.telegram.bot_token');

        if (! is_string($token) || $token === '') {
            Log::error('Telegram notification skipped: bot token is not configured.');

            return false;
        }

        if ($chatId === null || $chatId === '') {
            Log::error('Telegram notification skipped: missing chat id.');

            return false;
        }

        try {
            $response = Http::asForm()->post(sprintf(self::ENDPOINT, $token), [
                'chat_id' => $chatId,
                'text' => $message,
            ]);
        } catch (Throwable $exception) {
            Log::error('Telegram notification failed: request threw an exception.', [
                'chat_id' => $chatId,
                'exception' => $exception->getMessage(),
            ]);

            return false;
        }

        if ($response->failed()) {
            Log::error('Telegram notification failed: API returned an error.', [
                'chat_id' => $chatId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        }

        return true;
    }
}
