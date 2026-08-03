<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\Financials\Services\FinancialAllocationService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Financials\StoreFinancialAllocationRequest;
use App\Http\Resources\FinancialAllocationResource;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use OpenApi\Attributes as OA;

final class TaskFinancialAllocationController extends Controller
{
    public function __construct(private readonly FinancialAllocationService $allocations) {}

    #[OA\Post(
        path: '/tasks/{task}/financial-allocations',
        summary: 'Allocate money from a fund to a task',
        security: [['bearerAuth' => []]],
        tags: ['Financial Allocations'],
        parameters: [
            new OA\Parameter(name: 'task', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/StoreFinancialAllocationRequest'),
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Allocation created',
                content: new OA\JsonContent(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/FinancialAllocation')]),
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden — not the task owner'),
            new OA\Response(response: 404, description: 'Task not found'),
            new OA\Response(
                response: 422,
                description: 'Validation error, or insufficient fund balance',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse'),
            ),
        ],
    )]
    public function store(StoreFinancialAllocationRequest $request, Task $task): JsonResponse
    {
        Gate::authorize('view', $task);

        return FinancialAllocationResource::make($this->allocations->allocate($task, $request->toData()))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }
}
