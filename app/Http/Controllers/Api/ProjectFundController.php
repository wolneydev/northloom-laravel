<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\Financials\Services\FundService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Financials\StoreFundRequest;
use App\Http\Resources\FundResource;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use OpenApi\Attributes as OA;

final class ProjectFundController extends Controller
{
    public function __construct(private readonly FundService $funds) {}

    #[OA\Get(
        path: '/projects/{project}/funds',
        summary: 'List a project\'s funds',
        security: [['bearerAuth' => []]],
        tags: ['Funds'],
        parameters: [
            new OA\Parameter(name: 'project', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'per_page', in: 'query', description: 'Items per page (max 100)', schema: new OA\Schema(type: 'integer', default: 15)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Paginated list of funds',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Fund')),
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

        return FundResource::collection($this->funds->listForProject($project->id, $perPage))->response();
    }

    #[OA\Post(
        path: '/projects/{project}/funds',
        summary: 'Create a fund for a project',
        security: [['bearerAuth' => []]],
        tags: ['Funds'],
        parameters: [
            new OA\Parameter(name: 'project', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/StoreFundRequest'),
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Fund created',
                content: new OA\JsonContent(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Fund')]),
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
    public function store(StoreFundRequest $request, Project $project): JsonResponse
    {
        Gate::authorize('view', $project);

        return FundResource::make($this->funds->create($project, $request->toData()))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }
}
