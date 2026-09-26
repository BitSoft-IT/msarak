<?php

namespace Tests\Support;

use App\Models\Answer;
use App\Models\AssessmentSession;
use App\Models\AssessmentVersion;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\User;

/**
 * Shared fixtures for the Q-03 assessment journey tests.
 *
 * The published-version builder mirrors the private createPublishedVersion()
 * helper of tests/Feature/AssessmentSessionTest.php verbatim (same columns,
 * same RIASEC distribution, same option pattern) so the journey suite keeps a
 * single canonical way to build a question bank instead of a second one.
 *
 * Users are always created through the model factory because the role column
 * is not mass-assignable (it is not part of User::$fillable).
 */
trait AssessmentJourneyFixtures
{
    private const RIASEC_CODES = ['R', 'I', 'A', 'S', 'E', 'C'];

    private function createStudentUser(): User
    {
        return User::factory()->create(['role' => 'student']);
    }

    private function createAdminUser(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    /**
     * Build a published, student-ready assessment version with a complete bank.
     *
     * Published means status = 'active' AND published_at IS NOT NULL, which is
     * exactly what PublishedAssessmentService::getPublishedVersion() requires.
     */
    private function createPublishedAssessmentVersion(int $versionNumber, int $questionCount = 18): AssessmentVersion
    {
        $version = AssessmentVersion::create([
            'version_number' => $versionNumber,
            'status' => 'active',
            'published_at' => now(),
        ]);

        for ($position = 1; $position <= $questionCount; $position++) {
            $question = Question::create([
                'assessment_version_id' => $version->id,
                'position' => $position,
                'scenario' => "سيناريو الموقف {$position} للإصدار {$versionNumber}",
            ]);

            for ($optionPosition = 1; $optionPosition <= 4; $optionPosition++) {
                QuestionOption::create([
                    'question_id' => $question->id,
                    'position' => $optionPosition,
                    'option_text' => "نص الخيار {$optionPosition} للموقف {$position}",
                    'riasec_code' => self::RIASEC_CODES[($optionPosition - 1) % 6],
                ]);
            }
        }

        return $version;
    }

    /**
     * A draft version: not published, so it can never start a session.
     *
     * Questions are intentionally omitted: the start path only reads the
     * assessment_versions columns and never the question bank, so an empty
     * draft is enough to exercise the "no valid published version" branch.
     */
    private function createDraftVersion(int $versionNumber): AssessmentVersion
    {
        return AssessmentVersion::create([
            'version_number' => $versionNumber,
            'status' => 'draft',
            'published_at' => null,
        ]);
    }

    /**
     * A retired version: it carries published_at but its status is no longer
     * valid for use, which is the "حالة الإصدار المنشور غير صالحة" case of H-03.
     */
    private function createRetiredVersion(int $versionNumber): AssessmentVersion
    {
        return AssessmentVersion::create([
            'version_number' => $versionNumber,
            'status' => 'retired',
            'published_at' => now(),
        ]);
    }

    /**
     * An active version that was never published: qualifies on status but not
     * on published_at, so it must be rejected as well.
     */
    private function createActiveUnpublishedVersion(int $versionNumber): AssessmentVersion
    {
        return AssessmentVersion::create([
            'version_number' => $versionNumber,
            'status' => 'active',
            'published_at' => null,
        ]);
    }

    private function createInProgressSession(User $user, AssessmentVersion $version): AssessmentSession
    {
        return AssessmentSession::create([
            'user_id' => $user->id,
            'assessment_version_id' => $version->id,
            'status' => 'in_progress',
            'started_at' => now(),
        ]);
    }

    /**
     * A completed session, as it would remain after a finished assessment.
     *
     * No Result row is created: the start path never reads results, and adding
     * one would imply a completion flow that is out of scope for this fixture.
     */
    private function createCompletedSession(User $user, AssessmentVersion $version): AssessmentSession
    {
        return AssessmentSession::create([
            'user_id' => $user->id,
            'assessment_version_id' => $version->id,
            'status' => 'completed',
            'started_at' => now()->subHour(),
            'completed_at' => now(),
        ]);
    }

    /**
     * A contract-shaped "primary option" answer payload.
     *
     * The two boolean flags are always sent explicitly false alongside the
     * primary pick, and ratings default to absent ("ratings اختيارية" in H-03).
     */
    private function createPrimaryAnswerPayload(int $primaryOptionId, array $ratings = []): array
    {
        return [
            'primary_option_id' => $primaryOptionId,
            'none_selected' => false,
            'unable_to_judge' => false,
            'ratings' => $ratings,
        ];
    }

    /**
     * A stable, order-independent snapshot of an answer's option ratings.
     *
     * Rows are keyed by question_option_id and returned sorted by it, so a
     * comparison after a rejected request never fails on DB row order alone:
     * H-03 does not guarantee the order of the ratings array.
     */
    private function captureRatingsSnapshot(Answer $answer): array
    {
        return $answer->optionRatings()
            ->orderBy('question_option_id')
            ->pluck('rating', 'question_option_id')
            ->all();
    }
}
