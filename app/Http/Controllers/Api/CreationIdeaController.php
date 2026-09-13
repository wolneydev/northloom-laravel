<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\CreationIdeas\Services\CreationIdeaService;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreationIdea\ShowCreationIdeaRequest;
use App\Http\Resources\CreationIdeaResource;
use OpenApi\Attributes as OA;

final class CreationIdeaController extends Controller
{
    public function __construct(
        private readonly CreationIdeaService $ideas,
    ) {}

    #[OA\Get(
        path: '/creation-ideas',
        summary: 'Suggest a project or task idea from Brazil-local time and the user calendar',
        security: [['bearerAuth' => []]],
        tags: ['Creation Ideas'],
        parameters: [
            new OA\Parameter(name: 'target', in: 'query', required: true, schema: new OA\Schema(type: 'string', enum: ['project', 'task'])),
            new OA\Parameter(name: 'project_id', in: 'query', description: 'Required when target is task; must be owned by the caller', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'at', in: 'query', description: 'Optional local datetime override', schema: new OA\Schema(type: 'string', format: 'date-time')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Suggestion only; nothing is persisted',
                content: new OA\JsonContent(ref: '#/components/schemas/CreationIdeaResponse'),
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(
                response: 422,
                description: 'Validation error',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse'),
            ),
        ],
    )]
    public function show(ShowCreationIdeaRequest $request): CreationIdeaResource
    {
        return CreationIdeaResource::make(
            $this->ideas->suggestForUser($request->user()->id, $request->toQuery()),
        );
    }
}
