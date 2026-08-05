<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\Conversations\Services\ConversationService;
use App\Http\Controllers\Controller;
use App\Http\Resources\ConversationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

/**
 * Thin HTTP boundary for the authenticated user's AI chat conversations.
 */
final class ConversationController extends Controller
{
    public function __construct(
        private readonly ConversationService $conversations,
    ) {}

    #[OA\Get(
        path: '/conversations',
        summary: "List the authenticated user's AI chat conversations and which agent handled each",
        security: [['bearerAuth' => []]],
        tags: ['Conversations'],
        parameters: [
            new OA\Parameter(name: 'per_page', in: 'query', description: 'Items per page', schema: new OA\Schema(type: 'integer', default: 15)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Paginated list of conversations',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Conversation')),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ],
    )]
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->integer('per_page', 15);

        $conversations = $this->conversations->listForUser($request->user(), $perPage);

        return ConversationResource::collection($conversations)->response();
    }

    #[OA\Get(
        path: '/conversations/{id}',
        summary: 'Show one conversation with its messages for reuse in the chat UI',
        security: [['bearerAuth' => []]],
        tags: ['Conversations'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Conversation with ordered messages',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/ConversationDetail'),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Conversation does not belong to the user'),
            new OA\Response(response: 404, description: 'Conversation not found'),
        ],
    )]
    public function show(Request $request, string $id): JsonResponse
    {
        $conversation = $this->conversations->getForUser($request->user(), $id);

        return (new ConversationResource($conversation))->response();
    }
}
