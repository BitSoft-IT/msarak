<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\ResourceNotFoundException;
use App\Exceptions\VersionNotDraftException;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpsertQuestionRequest;
use App\Models\AssessmentVersion;
use App\Models\Question;
use App\Services\AssessmentVersionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class QuestionController extends Controller
{
    public function __construct(
        private readonly AssessmentVersionService $versionService,
    ) {}

    /**
     * Empty question form for a draft (F-05). Non-draft versions have no
     * editable surface: B-05 is the rule owner, the UI mirrors it with 409
     * surfaced as redirect+flash on the browser path.
     */
    public function create(AssessmentVersion $assessmentVersion): View|RedirectResponse
    {
        if ($assessmentVersion->status !== 'draft') {
            return $this->notDraftRedirect();
        }

        return view('admin.questions.form', [
            'version' => $assessmentVersion,
            'question' => null,
            'position' => $assessmentVersion->questions()->count() + 1,
        ]);
    }

    public function edit(AssessmentVersion $assessmentVersion, Question $question): View|RedirectResponse
    {
        abort_unless($question->assessment_version_id === $assessmentVersion->id, 404);

        if ($assessmentVersion->status !== 'draft') {
            return $this->notDraftRedirect();
        }

        $question->load(['questionOptions' => fn ($q) => $q->orderBy('position')]);

        return view('admin.questions.form', [
            'version' => $assessmentVersion,
            'question' => $question,
            'position' => $question->position,
        ]);
    }

    public function store(UpsertQuestionRequest $request, AssessmentVersion $assessmentVersion): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            $question = $this->versionService->addQuestion($assessmentVersion, $request->validated());

            return response()->json([
                'data' => $this->questionPayload($question),
                'message' => 'أُضيف السؤال.',
            ], 201);
        }

        try {
            $question = $this->versionService->addQuestion($assessmentVersion, $request->validated());
        } catch (VersionNotDraftException|ResourceNotFoundException $e) {
            return redirect()
                ->route('admin.assessment-versions.show', $assessmentVersion)
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.assessment-versions.show', $assessmentVersion)
            ->with('success', 'أُضيف السؤال.');
    }

    public function update(
        UpsertQuestionRequest $request,
        AssessmentVersion $assessmentVersion,
        Question $question,
    ): JsonResponse|RedirectResponse {
        abort_unless($question->assessment_version_id === $assessmentVersion->id, 404);

        if ($request->expectsJson()) {
            $question = $this->versionService->replaceQuestion($assessmentVersion, $question, $request->validated());

            return response()->json([
                'data' => $this->questionPayload($question),
                'message' => 'حُدّث السؤال.',
            ]);
        }

        try {
            $question = $this->versionService->replaceQuestion($assessmentVersion, $question, $request->validated());
        } catch (VersionNotDraftException|ResourceNotFoundException $e) {
            return redirect()
                ->route('admin.assessment-versions.show', $assessmentVersion)
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.assessment-versions.show', $assessmentVersion)
            ->with('success', 'حُدّث السؤال.');
    }

    public function destroy(Request $request, AssessmentVersion $assessmentVersion, Question $question): JsonResponse|RedirectResponse|Response
    {
        abort_unless($question->assessment_version_id === $assessmentVersion->id, 404);

        if ($request->expectsJson()) {
            $this->versionService->deleteQuestion($assessmentVersion, $question);

            return response()->noContent();
        }

        try {
            $this->versionService->deleteQuestion($assessmentVersion, $question);
        } catch (VersionNotDraftException|ResourceNotFoundException $e) {
            return redirect()
                ->route('admin.assessment-versions.show', $assessmentVersion)
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.assessment-versions.show', $assessmentVersion)
            ->with('success', 'حُذف السؤال.');
    }

    /**
     * @return array<string, mixed>
     */
    private function questionPayload(Question $question): array
    {
        return [
            'id' => $question->id,
            'position' => $question->position,
            'scenario' => $question->scenario,
            'options' => $question->questionOptions
                ->sortBy('position')
                ->map(fn ($option): array => [
                    'id' => $option->id,
                    'position' => $option->position,
                    'text' => $option->option_text,
                    'riasec_code' => $option->riasec_code,
                ])->values()->all(),
        ];
    }

    private function notDraftRedirect(): RedirectResponse
    {
        return redirect()
            ->route('admin.assessment-versions.index')
            ->with('error', 'إصدار التقييم غير قابل للتعديل.');
    }
}
