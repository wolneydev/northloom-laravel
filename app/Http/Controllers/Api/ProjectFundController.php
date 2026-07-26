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

final class ProjectFundController extends Controller
{
    public function __construct(private readonly FundService $funds) {}

    public function index(Request $request, Project $project): JsonResponse
    {
        Gate::authorize('view', $project);
        $perPage = max(1, min(100, (int) $request->integer('per_page', 15)));

        return FundResource::collection($this->funds->listForProject($project->id, $perPage))->response();
    }

    public function store(StoreFundRequest $request, Project $project): JsonResponse
    {
        Gate::authorize('view', $project);

        return FundResource::make($this->funds->create($project, $request->toData()))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }
}
