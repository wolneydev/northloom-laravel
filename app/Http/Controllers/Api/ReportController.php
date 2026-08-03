<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\Reports\Services\ReportService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Report\ShowReportRequest;
use App\Http\Resources\ProjectReportResource;
use App\Http\Resources\TaskResource;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

final class ReportController extends Controller
{
    public function __construct(
        private readonly ReportService $reports,
    ) {}

    #[OA\Get(
        path: '/reports',
        summary: 'Build an aggregated report of projects and/or tasks',
        security: [['bearerAuth' => []]],
        tags: ['Reports'],
        parameters: [
            new OA\Parameter(name: 'report_type', in: 'query', required: true, schema: new OA\Schema(type: 'string', enum: ['projects', 'tasks', 'both'])),
            new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'string', enum: ['pending', 'in_progress', 'completed', 'cancelled'])),
            new OA\Parameter(name: 'start_date', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'end_date', in: 'query', description: 'Must be on or after start_date', schema: new OA\Schema(type: 'string', format: 'date')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Report data, scoped to filters and to the authenticated user',
                content: new OA\JsonContent(ref: '#/components/schemas/ReportResponse'),
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(
                response: 422,
                description: 'Validation error',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse'),
            ),
        ],
    )]
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
