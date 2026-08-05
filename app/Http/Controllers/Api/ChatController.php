<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Ai\Agents\HospitableChatAgent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\SendChatMessageRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Laravel\Ai\Models\Conversation;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/**
 * Thin HTTP boundary for the authenticated user's AI chat.
 *
 * The agent uses the local Hospitable MCP server tools to create/update
 * projects, tasks, funds, costs, allocations, Telegram settings, and reports.
 */
final class ChatController extends Controller
{
    #[OA\Post(
        path: '/chat',
        summary: 'Send a message to the Hospitable MCP-backed chat agent',
        security: [['bearerAuth' => []]],
        tags: ['Chat'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['message'],
                properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'Crie um projeto chamado ERP com moeda BRL'),
                    new OA\Property(property: 'conversation_id', type: 'string', format: 'uuid', nullable: true),
                ],
            ),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Assistant reply',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'conversation_id', type: 'string', format: 'uuid'),
                                new OA\Property(property: 'message', type: 'string'),
                                new OA\Property(
                                    property: 'tool_calls',
                                    type: 'array',
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: 'id', type: 'string'),
                                            new OA\Property(property: 'name', type: 'string'),
                                            new OA\Property(property: 'arguments', type: 'object'),
                                        ],
                                        type: 'object',
                                    ),
                                ),
                            ],
                            type: 'object',
                        ),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Conversation does not belong to the user'),
            new OA\Response(response: 404, description: 'Conversation not found'),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(response: 502, description: 'Agent or MCP failure'),
        ],
    )]
    public function store(SendChatMessageRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $message = trim((string) $request->validated('message'));
        $conversationId = $request->validated('conversation_id');

        $agent = new HospitableChatAgent;

        if (is_string($conversationId) && $conversationId !== '') {
            $this->assertConversationOwnedBy($conversationId, $user);
            $agent->continue($conversationId, as: $user);
        } else {
            $agent->forUser($user);
        }

        try {
            $response = $agent->prompt($message, timeout: 120);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'The assistant could not complete this request. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 502);
        }

        return response()->json([
            'data' => [
                'conversation_id' => $response->conversationId,
                'message' => (string) $response,
                'tool_calls' => $response->toolCalls
                    ->map(fn ($call) => [
                        'id' => $call->id,
                        'name' => $call->name,
                        'arguments' => $call->arguments,
                    ])
                    ->values()
                    ->all(),
            ],
        ]);
    }

    private function assertConversationOwnedBy(string $conversationId, User $user): void
    {
        $conversation = Conversation::query()->find($conversationId);

        if ($conversation === null) {
            throw new NotFoundHttpException('Conversation not found.');
        }

        $sameType = $conversation->participant_type === Conversation::participantType($user);
        $sameKey = (string) $conversation->participant_id === (string) Conversation::participantKey($user);

        if (! $sameType || ! $sameKey) {
            throw new AccessDeniedHttpException('This conversation does not belong to you.');
        }
    }
}
