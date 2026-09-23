<?php

namespace App\Http\Controllers;

use App\Exceptions\ResourceNotFoundException;
use App\Models\Result;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ResultController extends Controller
{
    /**
     * Return the current student's historical results,
     * newest first.
     */
    public function index(Request $request): JsonResponse
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

    /**
     * Display one historical result owned by the current student.
     */
    public function show(Result $result): JsonResponse
    {
        if (Gate::denies('view', $result)) {
            throw new ResourceNotFoundException;
        }

        $result->load([
            'resultScores',
            'resultRecommendations' => function ($query): void {
                $query->orderBy('display_order');
            },
        ]);

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
}
