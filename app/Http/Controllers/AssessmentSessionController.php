<?php

namespace App\Http\Controllers;

use App\Exceptions\ResourceNotFoundException;
use App\Models\AssessmentSession;
use App\Services\AssessmentSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AssessmentSessionController extends Controller
{
    public function __construct(
        protected AssessmentSessionService $sessionService
    ) {}

    /**
     * Start a new assessment session or resume an existing in-progress one.
     */
    public function store(Request $request): JsonResponse
    {
        $result = $this->sessionService->startOrResumeSession($request->user());
        $session = $result['session'];
        $resumed = $result['resumed'];

        $statusCode = $resumed ? 200 : 201;
        $message = $resumed ? 'تم استئناف التقييم.' : 'بدأ التقييم.';

        return response()->json([
            'data' => [
                'session_id' => $session->id,
                'status' => $session->status,
                'assessment_version_id' => $session->assessment_version_id,
                'resumed' => $resumed,
                'redirect_url' => "/assessment/sessions/{$session->id}",
            ],
            'message' => $message,
        ], $statusCode);
    }

    /**
     * Display the assessment session and resume state.
     */
    public function show(Request $request, AssessmentSession $assessmentSession): JsonResponse
    {
        if (Gate::denies('view', $assessmentSession)) {
            throw new ResourceNotFoundException();
        }

        $data = $this->sessionService->getSessionState($assessmentSession);

        return response()->json([
            'data' => $data,
            'message' => 'تم تحميل جلسة التقييم.',
        ], 200);
    }
}
