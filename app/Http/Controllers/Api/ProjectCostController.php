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

final class ProjectCostController extends Controller
{
    public function __construct(private readonly CostService $costs) {}

    public function index(Request $request, Project $project): JsonResponse
    {
        Gate::authorize('view', $project);
        $perPage = max(1, min(100, (int) $request->integer('per_page', 15)));

        return CostResource::collection($this->costs->listForProject($project->id, $perPage))->response();
    }

    public function store(StoreCostRequest $request, Project $project): JsonResponse
    {
        Gate::authorize('view', $project);

        return CostResource::make($this->costs->create($project, $request->toData()))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }
}
