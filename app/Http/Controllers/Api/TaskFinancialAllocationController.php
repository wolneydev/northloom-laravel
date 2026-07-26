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

final class TaskFinancialAllocationController extends Controller
{
    public function __construct(private readonly FinancialAllocationService $allocations) {}

    public function store(StoreFinancialAllocationRequest $request, Task $task): JsonResponse
    {
        Gate::authorize('view', $task);

        return FinancialAllocationResource::make($this->allocations->allocate($task, $request->toData()))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }
}
