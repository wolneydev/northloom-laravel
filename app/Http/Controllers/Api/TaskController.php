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

    public function index(IndexTaskRequest $request): JsonResponse
    {
        $perPage = (int) $request->integer('per_page', 15);

        $tasks = $this->tasks->listForUser($request->user()->id, $request->toFilters(), $perPage);

        return TaskResource::collection($tasks)->response();
    }

    public function store(StoreTaskRequest $request): JsonResponse
    {
        $task = $this->tasks->create($request->toData());

        return TaskResource::make($task)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Task $task): JsonResponse
    {
        Gate::authorize('view', $task);

        $taskWithProject = $task->load('project');

        return TaskResource::make($taskWithProject)->response();
    }

    public function update(UpdateTaskRequest $request, Task $task): JsonResponse
    {
        Gate::authorize('update', $task);

        $updatedTask = $this->tasks->update($task, $request->toData());

        return TaskResource::make($updatedTask)->response();
    }

    public function destroy(Task $task): JsonResponse
    {
        Gate::authorize('delete', $task);

        $this->tasks->delete($task);

        return response()->json(status: Response::HTTP_NO_CONTENT);
    }
}
