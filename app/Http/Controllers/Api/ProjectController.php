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

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->integer('per_page', 15);

        $projects = $this->projects->listForUser($request->user()->id, $perPage);

        return ProjectResource::collection($projects)->response();
    }

    public function store(StoreProjectRequest $request): JsonResponse
    {
        $project = $this->projects->create($request->toData());

        return ProjectResource::make($project)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Project $project): JsonResponse
    {
        Gate::authorize('view', $project);

        return ProjectResource::make($project)->response();
    }

    public function update(UpdateProjectRequest $request, Project $project): JsonResponse
    {
        Gate::authorize('update', $project);

        $updatedProject = $this->projects->update($project, $request->toData());

        return ProjectResource::make($updatedProject)->response();
    }

    public function destroy(Project $project): JsonResponse
    {
        Gate::authorize('delete', $project);

        $this->projects->delete($project);

        return response()->json(status: Response::HTTP_NO_CONTENT);
    }
}
