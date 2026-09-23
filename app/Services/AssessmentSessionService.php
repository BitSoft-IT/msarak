<?php

namespace App\Services;

use App\Exceptions\AnswerInvalidException;
use App\Exceptions\SessionCompletedException;
use App\Models\Answer;
use App\Models\AnswerOptionRating;
use App\Models\AssessmentSession;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;

class AssessmentSessionService
{
    public function __construct(
        protected PublishedAssessmentService $publishedAssessmentService
    ) {}

    /**
     * Start a new assessment session or resume an existing in-progress one.
     *
     * To prevent race conditions under concurrent start requests for the same
     * student, the operation runs inside a database transaction that acquires
     * an exclusive row-level lock (lockForUpdate) on the stable User record
     * (users.id = $user->id). This serializes concurrent requests for that user
     * in multi-worker environments (MySQL/InnoDB) without blocking other users
     * or requiring schema modifications.
     *
     * @return array{session: AssessmentSession, resumed: bool}
     */
    public function startOrResumeSession(User $user): array
    {
        $publishedVersion = $this->publishedAssessmentService->getPublishedVersion();

        return DB::transaction(function () use ($user, $publishedVersion): array {
            // Pessimistic exclusive lock on the stable user record
            User::query()->where('id', $user->id)->lockForUpdate()->first();

            $existingSession = AssessmentSession::query()
                ->where('user_id', $user->id)
                ->where('assessment_version_id', $publishedVersion->id)
                ->where('status', 'in_progress')
                ->first();

            if ($existingSession) {
                return [
                    'session' => $existingSession,
                    'resumed' => true,
                ];
            }

            $newSession = AssessmentSession::create([
                'user_id' => $user->id,
                'assessment_version_id' => $publishedVersion->id,
                'status' => 'in_progress',
                'started_at' => now(),
            ]);

            return [
                'session' => $newSession,
                'resumed' => false,
            ];
        });
    }

    /**
     * Get the complete state of a session for resuming, conforming to H-03 contract.
     */
    public function getSessionState(AssessmentSession $session): array
    {
        $session->load([
            'assessmentVersion.questions' => function (Relation $query): void {
                $query->orderBy('position');
            },
            'assessmentVersion.questions.questionOptions' => function (Relation $query): void {
                $query->orderBy('position');
            },
            'answers.optionRatings',
        ]);

        $questions = $session->assessmentVersion->questions->map(function (Question $question): array {
            return [
                'question_id' => $question->id,
                'position' => $question->position,
                'scenario' => $question->scenario,
                'options' => $question->questionOptions->map(function (QuestionOption $option): array {
                    return [
                        'option_id' => $option->id,
                        'option_text' => $option->option_text,
                    ];
                })->values()->all(),
            ];
        })->values()->all();

        $savedAnswers = $session->answers->map(function (Answer $answer): array {
            return [
                'question_id' => $answer->question_id,
                'primary_option_id' => $answer->primary_option_id,
                'none_selected' => $answer->response_type === 'none',
                'unable_to_judge' => $answer->response_type === 'cannot_judge',
                'ratings' => $answer->optionRatings->map(function (AnswerOptionRating $rating): array {
                    return [
                        'option_id' => $rating->question_option_id,
                        'rating' => (int) $rating->rating,
                    ];
                })->values()->all(),
            ];
        })->values()->all();

        $answeredQuestionIds = $session->answers->pluck('question_id')->all();
        $processed = count($answeredQuestionIds);
        $total = $session->assessmentVersion->questions->count();

        $firstUnanswered = $session->assessmentVersion->questions
            ->first(fn (Question $q): bool => ! in_array($q->id, $answeredQuestionIds, true));

        $currentPosition = $firstUnanswered ? $firstUnanswered->position : ($total > 0 ? $total : 1);

        return [
            'session' => [
                'session_id' => $session->id,
                'status' => $session->status,
                'assessment_version_id' => $session->assessment_version_id,
            ],
            'progress' => [
                'processed' => $processed,
                'total' => $total,
                'current_position' => $currentPosition,
            ],
            'questions' => $questions,
            'saved_answers' => $savedAnswers,
        ];
    }

    /**
     * Atomically save or update an answer within an assessment session.
     */
    public function saveAnswer(AssessmentSession $session, Question $question, array $data): array
    {
        $noneSelected = filter_var($data['none_selected'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $unableToJudge = filter_var($data['unable_to_judge'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $primaryOptionId = $data['primary_option_id'] ?? null;

        if ($unableToJudge) {
            $responseType = 'cannot_judge';
            $primaryOptionId = null;
            $ratings = [];
        } elseif ($noneSelected) {
            $responseType = 'none';
            $primaryOptionId = null;
            $ratings = $data['ratings'] ?? [];
        } elseif (! is_null($primaryOptionId)) {
            $responseType = 'option';
            $primaryOptionId = (int) $primaryOptionId;
            $ratings = $data['ratings'] ?? [];
        } else {
            throw new AnswerInvalidException('يجب اختيار حالة إجابة صالحة.');
        }

        return DB::transaction(function () use ($session, $question, $responseType, $primaryOptionId, $ratings): array {
            // The route-bound model may be stale. Locking the persisted row
            // serializes saves with completion and concurrent first saves.
            $lockedSession = AssessmentSession::query()
                ->whereKey($session->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedSession->status === 'completed') {
                throw new SessionCompletedException;
            }

            if ($question->assessment_version_id !== $lockedSession->assessment_version_id) {
                throw new AnswerInvalidException('السؤال لا ينتمي إلى إصدار جلسة التقييم.');
            }

            $answer = Answer::where('assessment_session_id', $session->id)
                ->where('question_id', $question->id)
                ->first();

            if ($answer) {
                $answer->update([
                    'response_type' => $responseType,
                    'primary_option_id' => $primaryOptionId,
                ]);
            } else {
                $answer = Answer::create([
                    'assessment_session_id' => $session->id,
                    'question_id' => $question->id,
                    'response_type' => $responseType,
                    'primary_option_id' => $primaryOptionId,
                ]);
            }

            // Synchronize ratings: remove old ratings completely and insert new ones
            $answer->optionRatings()->delete();

            $formattedRatings = [];
            if (! empty($ratings)) {
                $now = now();
                $ratingRows = [];
                foreach ($ratings as $r) {
                    $ratingRows[] = [
                        'answer_id' => $answer->id,
                        'question_option_id' => (int) $r['option_id'],
                        'rating' => (int) $r['rating'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                    $formattedRatings[] = [
                        'option_id' => (int) $r['option_id'],
                        'rating' => (int) $r['rating'],
                    ];
                }
                AnswerOptionRating::insert($ratingRows);
            }

            $processed = $lockedSession->answers()->count();
            $total = $lockedSession->assessmentVersion->questions()->count();

            return [
                'question_id' => $question->id,
                'saved' => true,
                'answer' => [
                    'primary_option_id' => $primaryOptionId,
                    'none_selected' => $responseType === 'none',
                    'unable_to_judge' => $responseType === 'cannot_judge',
                    'ratings' => $formattedRatings,
                ],
                'progress' => [
                    'processed' => $processed,
                    'total' => $total,
                ],
            ];
        });
    }
}
