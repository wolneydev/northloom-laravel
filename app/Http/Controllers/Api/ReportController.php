<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\Reports\Services\ReportService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Report\ShowReportRequest;
use App\Http\Resources\ProjectReportResource;
use App\Http\Resources\TaskResource;
use Illuminate\Http\JsonResponse;

final class ReportController extends Controller
{
    public function __construct(
        private readonly ReportService $reports,
    ) {}

    public function show(ShowReportRequest $request): JsonResponse
    {
        $filters = $request->toFilters();
        $report = $this->reports->buildForUser($request->user()->id, $filters);

        $data = [
            'filters' => [
                'report_type' => $filters->report_type,
                'status' => $filters->status,
                'start_date' => $filters->start_date,
                'end_date' => $filters->end_date,
            ],
        ];

        if (isset($report['projects'])) {
            $data['projects'] = ProjectReportResource::collection($report['projects'])->resolve($request);
        }

        if (isset($report['tasks'])) {
            $data['tasks'] = TaskResource::collection($report['tasks'])->resolve($request);
        }

        return response()->json(['data' => $data]);
    }
}
