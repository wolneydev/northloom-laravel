<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\Notifications\Contracts\TelegramNotificationServiceInterface;
use App\Domain\Users\Services\UserService;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\UpdateTelegramSettingsRequest;
use App\Http\Resources\TelegramSettingsResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Thin HTTP boundary for the authenticated user's Telegram preferences.
 *
 * The chat id and the enabled flag are the only settings exposed; the bot
 * token never leaves the server configuration.
 */
final class TelegramSettingsController extends Controller
{
    public function __construct(
        private readonly UserService $users,
        private readonly TelegramNotificationServiceInterface $telegram,
    ) {}

    public function show(Request $request): JsonResponse
    {
        return TelegramSettingsResource::make($request->user())->response();
    }

    public function update(UpdateTelegramSettingsRequest $request): JsonResponse
    {
        $user = $this->users->updateTelegramSettings($request->user(), $request->toData());

        return TelegramSettingsResource::make($user)->response();
    }

    /**
     * Send a test message to the authenticated user's Telegram chat so they can
     * confirm the bot token and their chat id are correctly configured.
     */
    public function test(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->telegram_chat_id === null || $user->telegram_chat_id === '') {
            return response()->json(
                ['message' => 'Configure um telegram_chat_id antes de enviar uma mensagem de teste.'],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $delivered = $this->telegram->send(
            $user->telegram_chat_id,
            "✅ Telegram configured successfully!\n\nYou'll receive reminders for your tasks here.",
        );

        if (! $delivered) {
            return response()->json(
                ['message' => 'Não foi possível enviar a mensagem de teste. Verifique o token do bot e o chat id.'],
                Response::HTTP_BAD_GATEWAY,
            );
        }

        return response()->json(['message' => 'Mensagem de teste enviada com sucesso.']);
    }
}
