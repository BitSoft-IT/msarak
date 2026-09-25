<?php

namespace App\Http\Controllers;

use App\Exceptions\ResourceNotFoundException;
use App\Models\Result;
use App\Services\SpecializationCatalogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ResultController extends Controller
{
    public function __construct(
        private readonly SpecializationCatalogService $catalog,
    ) {}

    /**
     * Return the current student's historical results, newest first.
     *
     * Content negotiation (F-04): browsers receive the results-history
     * view; JSON clients keep the H-04 contract response byte-identical.
     */
    public function index(Request $request): JsonResponse|View
    {
        $results = Result::query()
            ->whereHas('assessmentSession', function ($query) use ($request): void {
                $query->where('user_id', $request->user()->id);
            })
            ->orderByDesc('created_at')
            ->get([
                'id',
                'assessment_session_id',
                'catalog_version',
                'scoring_version',
                'created_at',
            ]);

        if ($request->expectsJson()) {
            return response()->json([
                'data' => $results->map(function (Result $result): array {
                    return [
                        'result_id' => $result->id,
                        'assessment_session_id' => $result->assessment_session_id,
                        'catalog_version' => $result->catalog_version,
                        'scoring_version' => $result->scoring_version,
                        'created_at' => $result->created_at?->toISOString(),
                    ];
                })->values()->all(),
                'message' => 'تم تحميل سجل النتائج.',
            ]);
        }

        return view('results.index', ['results' => $results]);
    }

    /**
     * Display one result owned by the current student.
     *
     * Scores and recommendations render exactly as stored — Backend
     * order is never altered here (F-04 binding rules). Ownership is
     * enforced before any content loads; a non-owner gets the same
     * 404 as a missing result (H-04 rule preserved).
     */
    public function show(Request $request, Result $result): JsonResponse|View
    {
        // Ownership is enforced before any content loads; a non-owner
        // gets the same 404 as a missing result. JSON clients keep the
        // byte-identical H-04 contract error; browsers get the 404 page.
        if (Gate::denies('view', $result)) {
            if ($request->expectsJson()) {
                throw new ResourceNotFoundException;
            }

            abort(404);
        }

        $result->load([
            'resultScores',
            'resultRecommendations' => function ($query): void {
                $query->orderBy('display_order');
            },
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'data' => [
                    'result_id' => $result->id,
                    'assessment_session_id' => $result->assessment_session_id,
                    'catalog_version' => $result->catalog_version,
                    'scoring_version' => $result->scoring_version,
                    'created_at' => $result->created_at?->toISOString(),

                    'scores' => $result->resultScores
                        ->map(function ($score): array {
                            return [
                                'riasec_code' => $score->riasec_code,
                                'score' => (float) $score->score,
                            ];
                        })
                        ->values()
                        ->all(),

                    'recommendations' => $result->resultRecommendations
                        ->map(function ($recommendation): array {
                            return [
                                'specialization_key' => $recommendation->specialization_key,
                                'name_snapshot' => $recommendation->name_snapshot,
                                'display_order' => $recommendation->display_order,
                                'similarity_score' => (float) $recommendation->similarity_score,
                                'rationale_snapshot' => $recommendation->rationale_snapshot,
                            ];
                        })
                        ->values()
                        ->all(),
                ],
                'message' => 'تم تحميل النتيجة.',
            ]);
        }

        return view('results.show', [
            'result' => $result,
            'catalogKeys' => array_column($this->catalog->all(), 'id'),
        ]);
    }
}
