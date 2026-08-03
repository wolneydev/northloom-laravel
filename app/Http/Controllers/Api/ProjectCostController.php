<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\Financials\Services\CostService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Financials\StoreCostRequest;
use App\Http\Resources\CostResource;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use OpenApi\Attributes as OA;

final class ProjectCostController extends Controller
{
    public function __construct(private readonly CostService $costs) {}

    #[OA\Get(
        path: '/projects/{project}/costs',
        summary: 'List a project\'s costs',
        security: [['bearerAuth' => []]],
        tags: ['Costs'],
        parameters: [
            new OA\Parameter(name: 'project', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'per_page', in: 'query', description: 'Items per page (max 100)', schema: new OA\Schema(type: 'integer', default: 15)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Paginated list of costs',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Cost')),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden — not the project owner'),
            new OA\Response(response: 404, description: 'Project not found'),
        ],
    )]
    public function index(Request $request, Project $project): JsonResponse
    {
        Gate::authorize('view', $project);
        $perPage = max(1, min(100, (int) $request->integer('per_page', 15)));

        return CostResource::collection($this->costs->listForProject($project->id, $perPage))->response();
    }

    #[OA\Post(
        path: '/projects/{project}/costs',
        summary: 'Create a cost for a project',
        security: [['bearerAuth' => []]],
        tags: ['Costs'],
        parameters: [
            new OA\Parameter(name: 'project', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/StoreCostRequest'),
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Cost created',
                content: new OA\JsonContent(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Cost')]),
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
    public function store(StoreCostRequest $request, Project $project): JsonResponse
    {
        Gate::authorize('view', $project);

        return CostResource::make($this->costs->create($project, $request->toData()))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }
}
