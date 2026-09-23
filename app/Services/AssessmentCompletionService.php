<?php

namespace App\Services;

use App\Exceptions\SessionNotReadyException;
use App\Models\AssessmentSession;
use App\Models\Result;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AssessmentCompletionService
{
    public const REQUIRED_QUESTION_COUNT = 18;

    public const MINIMUM_VALID_QUESTIONS = 15;

    public function __construct(
        protected ScoringService $scoringService,
        protected RecommendationService $recommendationService,
        protected SpecializationCatalogService $catalogService,
    ) {}

    public function complete(AssessmentSession $session): Result
    {
        return DB::transaction(function () use ($session): Result {
            $lockedSession = AssessmentSession::query()
                ->whereKey($session->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $existingResult = Result::query()
                ->where('assessment_session_id', $lockedSession->id)
                ->first();

            if ($existingResult) {
                return $existingResult->load(['resultScores', 'resultRecommendations']);
            }

            if ($lockedSession->status === 'completed') {
                throw new RuntimeException('A completed assessment session has no result.');
            }

            $lockedSession->load([
                'assessmentVersion.questions' => function (Relation $query): void {
                    $query->orderBy('position');
                },
                'assessmentVersion.questions.questionOptions',
                'answers.question.questionOptions',
                'answers.primaryOption',
                'answers.optionRatings.questionOption',
            ]);

            $questions = $lockedSession->assessmentVersion->questions;
            $answers = $lockedSession->answers;
            $versionQuestionIds = $questions->pluck('id')->sort()->values();
            $answeredQuestionIds = $answers->pluck('question_id')->unique()->sort()->values();

            if ($questions->count() !== self::REQUIRED_QUESTION_COUNT
                || $answers->count() !== self::REQUIRED_QUESTION_COUNT
                || $answeredQuestionIds->all() !== $versionQuestionIds->all()) {
                throw new SessionNotReadyException('يجب معالجة المواقف الثمانية عشر قبل إكمال التقييم.');
            }

            $validQuestions = $answers->where('response_type', '!=', 'cannot_judge')->count();
            if ($validQuestions < self::MINIMUM_VALID_QUESTIONS) {
                throw new SessionNotReadyException('يلزم وجود 15 موقفًا صالحًا على الأقل لإنتاج النتيجة.');
            }

            $scores = $this->scoringService->calculate($answers);
            $recommendations = $this->recommendationService->recommend($scores);

            $result = Result::create([
                'assessment_session_id' => $lockedSession->id,
                'catalog_version' => $this->catalogService->version(),
                'scoring_version' => ScoringService::VERSION,
            ]);

            $result->resultScores()->createMany(collect($scores)->map(
                fn (float $score, string $code): array => [
                    'riasec_code' => $code,
                    'score' => $score,
                ]
            )->values()->all());

            $result->resultRecommendations()->createMany(collect($recommendations)->map(
                fn (array $recommendation, int $index): array => [
                    'specialization_key' => $recommendation['specialization_key'],
                    'name_snapshot' => $recommendation['name'],
                    'display_order' => $index + 1,
                    'similarity_score' => $recommendation['similarity_score'],
                    'rationale_snapshot' => $recommendation['rationale'],
                ]
            )->values()->all());

            $lockedSession->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            return $result->load(['resultScores', 'resultRecommendations']);
        });
    }
}
