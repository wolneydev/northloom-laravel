<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\Tasks\Services\TaskService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Task\IndexTaskRequest;
use App\Http\Requests\Task\StoreTaskRequest;
use App\Http\Requests\Task\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use OpenApi\Attributes as OA;

/**
 * Thin HTTP boundary for the calendar task resource.
 *
 * Validation lives in form requests, business rules in the service,
 * authorization in the policy, and serialization in the resource.
 */
final class TaskController extends Controller
{
    public function __construct(
        private readonly TaskService $tasks,
    ) {}

    #[OA\Get(
        path: '/tasks',
        summary: 'List calendar tasks owned by the authenticated user',
        security: [['bearerAuth' => []]],
        tags: ['Tasks'],
        parameters: [
            new OA\Parameter(name: 'per_page', in: 'query', description: 'Items per page', schema: new OA\Schema(type: 'integer', default: 15)),
            new OA\Parameter(name: 'start', in: 'query', description: 'Filter tasks from this date (inclusive)', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'end', in: 'query', description: 'Filter tasks up to this date (inclusive)', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'project_id', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'string', enum: ['pending', 'in_progress', 'completed', 'cancelled'])),
            new OA\Parameter(name: 'priority', in: 'query', schema: new OA\Schema(type: 'string', enum: ['low', 'medium', 'high'])),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Paginated list of tasks',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Task')),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ],
    )]
    public function index(IndexTaskRequest $request): JsonResponse
    {
        $perPage = (int) $request->integer('per_page', 15);

        $tasks = $this->tasks->listForUser($request->user()->id, $request->toFilters(), $perPage);

        return TaskResource::collection($tasks)->response();
    }

    #[OA\Post(
        path: '/tasks',
        summary: 'Create a calendar task',
        security: [['bearerAuth' => []]],
        tags: ['Tasks'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/StoreTaskRequest'),
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Task created',
                content: new OA\JsonContent(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Task')]),
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(
                response: 422,
                description: 'Validation error',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse'),
            ),
        ],
    )]
    public function store(StoreTaskRequest $request): JsonResponse
    {
        $task = $this->tasks->create($request->toData());

        return TaskResource::make($task)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    #[OA\Get(
        path: '/tasks/{task}',
        summary: 'Show a calendar task',
        security: [['bearerAuth' => []]],
        tags: ['Tasks'],
        parameters: [
            new OA\Parameter(name: 'task', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Task details',
                content: new OA\JsonContent(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Task')]),
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden — not the task owner'),
            new OA\Response(response: 404, description: 'Task not found'),
        ],
    )]
    public function show(Task $task): JsonResponse
    {
        Gate::authorize('view', $task);

        $taskWithProject = $task->load('project');

        return TaskResource::make($taskWithProject)->response();
    }

    #[OA\Put(
        path: '/tasks/{task}',
        summary: 'Update a calendar task',
        security: [['bearerAuth' => []]],
        tags: ['Tasks'],
        parameters: [
            new OA\Parameter(name: 'task', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/UpdateTaskRequest'),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Task updated',
                content: new OA\JsonContent(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Task')]),
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden — not the task owner'),
            new OA\Response(response: 404, description: 'Task not found'),
            new OA\Response(
                response: 422,
                description: 'Validation error',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse'),
            ),
        ],
    )]
    #[OA\Patch(
        path: '/tasks/{task}',
        summary: 'Partially update a calendar task',
        security: [['bearerAuth' => []]],
        tags: ['Tasks'],
        parameters: [
            new OA\Parameter(name: 'task', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/UpdateTaskRequest'),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Task updated',
                content: new OA\JsonContent(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Task')]),
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden — not the task owner'),
            new OA\Response(response: 404, description: 'Task not found'),
            new OA\Response(
                response: 422,
                description: 'Validation error',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse'),
            ),
        ],
    )]
    public function update(UpdateTaskRequest $request, Task $task): JsonResponse
    {
        Gate::authorize('update', $task);

        $updatedTask = $this->tasks->update($task, $request->toData());

        return TaskResource::make($updatedTask)->response();
    }

    #[OA\Delete(
        path: '/tasks/{task}',
        summary: 'Delete a calendar task',
        security: [['bearerAuth' => []]],
        tags: ['Tasks'],
        parameters: [
            new OA\Parameter(name: 'task', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Task deleted'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden — not the task owner'),
            new OA\Response(response: 404, description: 'Task not found'),
        ],
    )]
    public function destroy(Task $task): JsonResponse
    {
        Gate::authorize('delete', $task);

        $this->tasks->delete($task);

        return response()->json(status: Response::HTTP_NO_CONTENT);
    }
}
