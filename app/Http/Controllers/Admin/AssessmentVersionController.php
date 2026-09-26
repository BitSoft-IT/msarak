<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\ResourceNotFoundException;
use App\Exceptions\VersionNotDraftException;
use App\Exceptions\VersionNotPublishableException;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateAssessmentDraftRequest;
use App\Models\AssessmentVersion;
use App\Services\AssessmentVersionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssessmentVersionController extends Controller
{
    public function __construct(
        private readonly AssessmentVersionService $versionService,
    ) {}

    /**
     * Assessment versions list (F-05 read surface, Alhareith-approved).
     * Read-only: versions and question counts come from the stored rows;
     * authorization remains entirely in the role:admin middleware.
     */
    public function index(): View
    {
        $versions = AssessmentVersion::query()
            ->withCount('questions')
            ->orderByDesc('version_number')
            ->get();

        return view('admin.versions.index', ['versions' => $versions]);
    }

    /**
     * One version with its questions and options (draft editing screen).
     */
    public function show(AssessmentVersion $assessmentVersion): View
    {
        $assessmentVersion->load([
            'questions' => fn ($query) => $query->orderBy('position'),
            'questions.questionOptions' => fn ($query) => $query->orderBy('position'),
        ]);

        return view('admin.versions.show', ['version' => $assessmentVersion]);
    }

    public function store(CreateAssessmentDraftRequest $request): JsonResponse|RedirectResponse
    {
        // The FormRequest keeps its own content negotiation: invalid input
        // answers 422 to JSON clients and redirects back with error bags to
        // browsers — identical validation rules in both paths.
        $result = $this->versionService->createOrGetDraft(
            $request->integer('source_version_id') ?: null
        );
        $version = $result['version'];

        if ($request->expectsJson()) {
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

        return redirect()
            ->route('admin.assessment-versions.show', $version)
            ->with('success', $result['created'] ? 'أُنشئت المسودة.' : 'أُعيدت المسودة المفتوحة.');
    }

    public function publish(Request $request, AssessmentVersion $assessmentVersion): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            // Exceptions bubble to their own JSON renderers — B-05 contract intact.
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

        try {
            $result = $this->versionService->publish($assessmentVersion);
        } catch (VersionNotDraftException|VersionNotPublishableException|ResourceNotFoundException $e) {
            // Browser path: same Backend messages, surfaced as a flash error.
            return redirect()
                ->route('admin.assessment-versions.show', $assessmentVersion)
                ->with('error', $e->getMessage());
        }

        $version = $result['version'];

        return redirect()
            ->route('admin.assessment-versions.show', $version)
            ->with('success', $result['already_published'] ? 'إصدار التقييم منشور بالفعل.' : 'نُشر إصدار التقييم.');
    }
}
