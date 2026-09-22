<?php

namespace App\Http\Controllers;

use App\Exceptions\AnswerInvalidException;
use App\Exceptions\AnswerSaveFailedException;
use App\Exceptions\ResourceNotFoundException;
use App\Exceptions\SessionCompletedException;
use App\Http\Requests\SaveAnswerRequest;
use App\Models\AssessmentSession;
use App\Models\Question;
use App\Services\AssessmentSessionService;
use Illuminate\Http\JsonResponse;
use Throwable;

class AssessmentAnswerController extends Controller
{
    public function __construct(
        protected AssessmentSessionService $sessionService
    ) {}

    /**
     * Save or update an answer for a question within an assessment session.
     */
    public function update(
        SaveAnswerRequest $request,
        AssessmentSession $assessmentSession,
        Question $question
    ): JsonResponse {
        try {
            $result = $this->sessionService->saveAnswer($assessmentSession, $question, $request->all());

            return response()->json([
                'data' => $result,
                'message' => 'حُفظت الإجابة.',
            ], 200);
        } catch (AnswerInvalidException|SessionCompletedException|ResourceNotFoundException $e) {
            throw $e;
        } catch (Throwable $e) {
            report($e);

            throw new AnswerSaveFailedException();
        }
    }
}
