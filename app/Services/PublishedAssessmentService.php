<?php

namespace App\Services;

use App\Exceptions\AssessmentUnavailableException;
use App\Models\AssessmentVersion;
use App\Models\Question;
use App\Models\QuestionOption;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * Reads the currently published assessment version and prepares a
 * student-safe view of its question bank.
 *
 * This service is strictly read-only. It never creates, updates, saves or
 * deletes AssessmentVersion, Question or QuestionOption rows, and it never
 * mutates position, status, published_at, scenario, option_text or
 * riasec_code. Shuffling is a presentation-only concern applied to in-memory
 * collections.
 *
 * RIASEC codes are used internally for scoring in later tasks, but they are
 * never included in the payload produced by getStudentPayload(). Field
 * mapping is explicit; toArray() is deliberately not used.
 *
 * The only public way to obtain a student payload is getStudentPayload(),
 * which resolves the published version itself. No public method accepts an
 * arbitrary AssessmentVersion, so a draft, retired or unpublished version
 * cannot become the source of student-facing data.
 */
class PublishedAssessmentService
{
    /**
     * Resolve the single assessment version that is published and valid.
     *
     * A version qualifies as published when status = 'active' AND
     * published_at IS NOT NULL. Exactly one such version must exist:
     * none at all means the assessment is unavailable, and more than one is
     * a data-integrity violation that is never resolved by silently picking
     * the first row.
     *
     * @throws AssessmentUnavailableException When zero or several versions qualify.
     */
    public function getPublishedVersion(): AssessmentVersion
    {
        $versions = AssessmentVersion::query()
            ->where('status', 'active')
            ->whereNotNull('published_at')
            ->orderBy('id')
            ->get();

        if ($versions->isEmpty()) {
            throw AssessmentUnavailableException::noPublishedVersion();
        }

        if ($versions->count() > 1) {
            throw AssessmentUnavailableException::multiplePublishedVersions();
        }

        return $versions->first();
    }

    /**
     * Build the student-safe payload for the currently published version.
     *
     * The version is resolved here rather than accepted as an argument, so no
     * caller can build a student payload from a draft, retired or unpublished
     * version by bypassing getPublishedVersion().
     *
     * Questions are loaded for that version only, ordered by position ASC,
     * with their options eager-loaded in the same query batch to avoid N+1.
     * The question count is whatever the version actually stores; it is never
     * sliced with take() or limit(), so a short or over-long bank surfaces
     * instead of being hidden.
     *
     * @param  bool  $shuffleOptions  Randomise option display order. This
     *                                reorders the in-memory collection only;
     *                                question_options.position in the database
     *                                is never modified.
     *
     * @throws AssessmentUnavailableException When no or several published versions exist.
     */
    public function getStudentPayload(bool $shuffleOptions = false): array
    {
        return $this->buildStudentPayloadForVersion(
            $this->getPublishedVersion(),
            $shuffleOptions
        );
    }

    /**
     * Load the questions and options of one published version and map them to
     * the student-safe shape.
     *
     * Private on purpose: it trusts the version it receives, so only the
     * published-version resolution inside getStudentPayload() may call it.
     */
    private function buildStudentPayloadForVersion(AssessmentVersion $version, bool $shuffleOptions): array
    {
        $version->load([
            'questions' => function (Relation $query): void {
                $query->orderBy('position');
            },
            'questions.questionOptions' => function (Relation $query): void {
                $query->orderBy('position');
            },
        ]);

        return [
            'assessment_version_id' => $version->id,
            'version_number' => $version->version_number,
            'questions' => $version->questions
                ->map(fn (Question $question): array => $this->mapQuestion($question, $shuffleOptions))
                ->all(),
        ];
    }

    /**
     * Map one question and its options to the student-safe shape.
     *
     * Only question_id, position, scenario and the option list are exposed.
     * Assessment-version linkage, timestamps and any scoring data stay out.
     */
    private function mapQuestion(Question $question, bool $shuffleOptions): array
    {
        $options = $shuffleOptions ? $question->questionOptions->shuffle() : $question->questionOptions;

        return [
            'question_id' => $question->id,
            'position' => $question->position,
            'scenario' => $question->scenario,
            'options' => $options
                ->map(fn (QuestionOption $option): array => [
                    'option_id' => $option->id,
                    'option_text' => $option->option_text,
                ])
                ->all(),
        ];
    }
}
