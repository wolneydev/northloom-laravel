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
use OpenApi\Attributes as OA;

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

    #[OA\Get(
        path: '/me/telegram',
        summary: "Show the authenticated user's Telegram preferences",
        security: [['bearerAuth' => []]],
        tags: ['Telegram'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Telegram preferences',
                content: new OA\JsonContent(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/TelegramSettings')]),
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ],
    )]
    public function show(Request $request): JsonResponse
    {
        return TelegramSettingsResource::make($request->user())->response();
    }

    #[OA\Put(
        path: '/me/telegram',
        summary: "Update the authenticated user's Telegram preferences",
        security: [['bearerAuth' => []]],
        tags: ['Telegram'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/UpdateTelegramSettingsRequest'),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Telegram preferences updated',
                content: new OA\JsonContent(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/TelegramSettings')]),
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(
                response: 422,
                description: 'Validation error',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse'),
            ),
        ],
    )]
    public function update(UpdateTelegramSettingsRequest $request): JsonResponse
    {
        $user = $this->users->updateTelegramSettings($request->user(), $request->toData());

        return TelegramSettingsResource::make($user)->response();
    }

    #[OA\Post(
        path: '/me/telegram/test',
        summary: 'Send a Telegram test message to the authenticated user',
        description: 'Confirms the bot token and the configured telegram_chat_id are working.',
        security: [['bearerAuth' => []]],
        tags: ['Telegram'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Test message sent successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse'),
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(
                response: 422,
                description: 'No telegram_chat_id configured',
                content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse'),
            ),
            new OA\Response(
                response: 502,
                description: 'Telegram delivery failed (invalid bot token or chat id)',
                content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse'),
            ),
        ],
    )]
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
