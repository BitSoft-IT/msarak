<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpsertQuestionRequest;
use App\Models\AssessmentVersion;
use App\Models\Question;
use App\Services\AssessmentVersionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class QuestionController extends Controller
{
    public function __construct(
        private readonly AssessmentVersionService $versionService,
    ) {}

    public function store(UpsertQuestionRequest $request, AssessmentVersion $assessmentVersion): JsonResponse
    {
        $question = $this->versionService->addQuestion($assessmentVersion, $request->validated());

        return response()->json([
            'data' => $this->questionPayload($question),
            'message' => 'أُضيف السؤال.',
        ], 201);
    }

    public function update(
        UpsertQuestionRequest $request,
        AssessmentVersion $assessmentVersion,
        Question $question,
    ): JsonResponse {
        $question = $this->versionService->replaceQuestion($assessmentVersion, $question, $request->validated());

        return response()->json([
            'data' => $this->questionPayload($question),
            'message' => 'حُدّث السؤال.',
        ]);
    }

    public function destroy(AssessmentVersion $assessmentVersion, Question $question): Response
    {
        $this->versionService->deleteQuestion($assessmentVersion, $question);

        return response()->noContent();
    }

    /** @return array<string, mixed> */
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
}
