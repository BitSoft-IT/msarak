<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateAssessmentDraftRequest;
use App\Models\AssessmentVersion;
use App\Services\AssessmentVersionService;
use Illuminate\Http\JsonResponse;

class AssessmentVersionController extends Controller
{
    public function __construct(
        private readonly AssessmentVersionService $versionService,
    ) {}

    public function store(CreateAssessmentDraftRequest $request): JsonResponse
    {
        $result = $this->versionService->createOrGetDraft($request->integer('source_version_id') ?: null);
        $version = $result['version'];

        return response()->json([
            'data' => [
                'id' => $version->id,
                'version_number' => $version->version_number,
                'status' => $version->status,
                'question_count' => $version->questions_count,
                'edit_url' => "/admin/assessment-versions/{$version->id}/edit",
            ],
            'message' => $result['created'] ? 'أُنشئت المسودة.' : 'أُعيدت المسودة المفتوحة.',
        ], $result['created'] ? 201 : 200);
    }

    public function publish(AssessmentVersion $assessmentVersion): JsonResponse
    {
        $result = $this->versionService->publish($assessmentVersion);
        $version = $result['version'];

        return response()->json([
            'data' => [
                'id' => $version->id,
                'version_number' => $version->version_number,
                'status' => $version->status,
                'published_at' => $version->published_at?->toISOString(),
            ],
            'message' => $result['already_published'] ? 'إصدار التقييم منشور بالفعل.' : 'نُشر إصدار التقييم.',
        ]);
    }
}
