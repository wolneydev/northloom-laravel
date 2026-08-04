<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\Projects\Services\ProjectService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Project\StoreProjectRequest;
use App\Http\Requests\Project\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use OpenApi\Attributes as OA;

/**
 * Thin HTTP boundary for the project resource.
 *
 * Validation lives in form requests, business rules in the service,
 * authorization in the policy, and serialization in the resource.
 */
final class ProjectController extends Controller
{
    public function __construct(
        private readonly ProjectService $projects,
    ) {}

    #[OA\Get(
        path: '/projects',
        summary: 'List projects owned by the authenticated user',
        security: [['bearerAuth' => []]],
        tags: ['Projects'],
        parameters: [
            new OA\Parameter(name: 'per_page', in: 'query', description: 'Items per page', schema: new OA\Schema(type: 'integer', default: 15)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Paginated list of projects',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Project')),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ],
    )]
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->integer('per_page', 15);

        $projects = $this->projects->listForUser($request->user()->id, $perPage);

        return ProjectResource::collection($projects)->response();
    }

    #[OA\Post(
        path: '/projects',
        summary: 'Create a project',
        security: [['bearerAuth' => []]],
        tags: ['Projects'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/StoreProjectRequest'),
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Project created',
                content: new OA\JsonContent(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Project')]),
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(
                response: 422,
                description: 'Validation error',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse'),
            ),
        ],
    )]
    public function store(StoreProjectRequest $request): JsonResponse
    {
        $project = $this->projects->create($request->toData());

        return ProjectResource::make($project)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    #[OA\Get(
        path: '/projects/{project}',
        summary: 'Show a project',
        security: [['bearerAuth' => []]],
        tags: ['Projects'],
        parameters: [
            new OA\Parameter(name: 'project', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Project details',
                content: new OA\JsonContent(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Project')]),
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden — not the project owner'),
            new OA\Response(response: 404, description: 'Project not found'),
        ],
    )]
    public function show(Project $project): JsonResponse
    {
        Gate::authorize('view', $project);

        return ProjectResource::make($project)->response();
    }

    #[OA\Put(
        path: '/projects/{project}',
        summary: 'Update a project',
        security: [['bearerAuth' => []]],
        tags: ['Projects'],
        parameters: [
            new OA\Parameter(name: 'project', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/UpdateProjectRequest'),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Project updated',
                content: new OA\JsonContent(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Project')]),
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden — not the project owner'),
            new OA\Response(response: 404, description: 'Project not found'),
            new OA\Response(
                response: 422,
                description: 'Validation error',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse'),
            ),
        ],
    )]
    #[OA\Patch(
        path: '/projects/{project}',
        summary: 'Partially update a project',
        security: [['bearerAuth' => []]],
        tags: ['Projects'],
        parameters: [
            new OA\Parameter(name: 'project', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/UpdateProjectRequest'),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Project updated',
                content: new OA\JsonContent(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Project')]),
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden — not the project owner'),
            new OA\Response(response: 404, description: 'Project not found'),
            new OA\Response(
                response: 422,
                description: 'Validation error',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse'),
            ),
        ],
    )]
    public function update(UpdateProjectRequest $request, Project $project): JsonResponse
    {
        Gate::authorize('update', $project);

        $updatedProject = $this->projects->update($project, $request->toData());

        return ProjectResource::make($updatedProject)->response();
    }

    #[OA\Delete(
        path: '/projects/{project}',
        summary: 'Delete a project',
        security: [['bearerAuth' => []]],
        tags: ['Projects'],
        parameters: [
            new OA\Parameter(name: 'project', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Project deleted'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden — not the project owner'),
            new OA\Response(response: 404, description: 'Project not found'),
        ],
    )]
    public function destroy(Project $project): JsonResponse
    {
        Gate::authorize('delete', $project);

        $this->projects->delete($project);

        return response()->json(status: Response::HTTP_NO_CONTENT);
    }
}
