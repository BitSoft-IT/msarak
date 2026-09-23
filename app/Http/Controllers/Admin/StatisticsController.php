<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StatisticsFilterRequest;
use App\Services\StatisticsService;
use Illuminate\Http\JsonResponse;

class StatisticsController extends Controller
{
    public function __construct(
        private readonly StatisticsService $statisticsService,
    ) {}

    public function index(StatisticsFilterRequest $request): JsonResponse
    {
        $filters = $request->validated();

        return response()->json([
            'data' => $this->statisticsService->summarize(
                $filters['from'] ?? null,
                $filters['to'] ?? null,
            ),
        ]);
    }
}
