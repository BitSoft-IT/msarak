<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StatisticsFilterRequest;
use App\Services\StatisticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class StatisticsController extends Controller
{
    public function __construct(
        private readonly StatisticsService $statisticsService,
    ) {}

    /**
     * Content negotiation (F-05): browsers receive the dashboard view;
     * JSON clients keep the B-05 contract response unchanged. The view
     * renders Backend metrics verbatim — no calculation happens here.
     */
    public function index(StatisticsFilterRequest $request): JsonResponse|View
    {
        $filters = $request->validated();

        $data = $this->statisticsService->summarize(
            $filters['from'] ?? null,
            $filters['to'] ?? null,
        );

        if ($request->expectsJson()) {
            return response()->json(['data' => $data]);
        }

        return view('admin.statistics', [
            'stats' => $data,
            'from' => $filters['from'] ?? null,
            'to' => $filters['to'] ?? null,
        ]);
    }
}
