<?php

namespace Tests\Feature\Database;

use App\Models\Answer;
use App\Models\AnswerOptionRating;
use App\Models\AssessmentSession;
use App\Models\AssessmentVersion;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Result;
use App\Models\ResultRecommendation;
use App\Models\ResultScore;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Q-02 (Phase 2) — Relationship integrity and foreign-key enforcement.
 *
 * Every foreign key in the schema is declared RESTRICT ON DELETE, which is the
 * database-level expression of the approved rule that no used version, session
 * or result may be removed. These tests prove the engine itself refuses the
 * deletion and refuses orphaned inserts — they do not rely on application
 * validation.
 *
 * Production code is never modified by these tests.
 */
class RelationshipConstraintsTest extends TestCase
{
    use RefreshDatabase;

    public function test_deleting_a_version_that_owns_questions_is_blocked(): void
    {
        // Arrange
        $version = $this->createVersion();
        Question::create([
            'assessment_version_id' => $version->id,
            'position' => 1,
            'scenario' => 'موقف اختبار',
        ]);

        // Act & Assert — RESTRICT must refuse the parent deletion.
        $this->assertDeletionIsBlocked($version, 'questions');
    }

    public function test_deleting_a_question_that_owns_options_is_blocked(): void
    {
        // Arrange
        $question = $this->createQuestion();

        QuestionOption::create([
            'question_id' => $question->id,
            'position' => 1,
            'option_text' => 'خيار اختبار',
            'riasec_code' => 'R',
        ]);

        // Act & Assert
        $this->assertDeletionIsBlocked($question, 'question_options');
    }

    public function test_deleting_a_question_that_owns_answers_is_blocked(): void
    {
        // Arrange
        $question = $this->createQuestion();
        $session = $this->createSession($question->assessment_version_id);

        Answer::create([
            'assessment_session_id' => $session->id,
            'question_id' => $question->id,
            'response_type' => 'none',
        ]);

        // Act & Assert
        $this->assertDeletionIsBlocked($question, 'answers');
    }

    public function test_deleting_a_user_that_owns_sessions_is_blocked(): void
    {
        // Arrange
        $user = User::factory()->create();
        $version = $this->createVersion();

        AssessmentSession::create([
            'user_id' => $user->id,
            'assessment_version_id' => $version->id,
            'started_at' => now(),
        ]);

        // Act & Assert
        $this->assertDeletionIsBlocked($user, 'assessment_sessions');
    }

    public function test_deleting_a_version_that_owns_sessions_is_blocked(): void
    {
        // Arrange
        $version = $this->createVersion();

        AssessmentSession::create([
            'user_id' => User::factory()->create()->id,
            'assessment_version_id' => $version->id,
            'started_at' => now(),
        ]);

        // Act & Assert
        $this->assertDeletionIsBlocked($version, 'assessment_sessions');
    }

    public function test_deleting_a_session_that_owns_answers_is_blocked(): void
    {
        // Arrange
        $question = $this->createQuestion();
        $session = $this->createSession($question->assessment_version_id);

        Answer::create([
            'assessment_session_id' => $session->id,
            'question_id' => $question->id,
            'response_type' => 'none',
        ]);

        // Act & Assert
        $this->assertDeletionIsBlocked($session, 'answers');
    }

    public function test_deleting_an_option_used_as_a_primary_pick_is_blocked(): void
    {
        // Arrange
        $question = $this->createQuestion();
        $option = $this->createOption($question);
        $session = $this->createSession($question->assessment_version_id);

        Answer::create([
            'assessment_session_id' => $session->id,
            'question_id' => $question->id,
            'primary_option_id' => $option->id,
            'response_type' => 'option',
        ]);

        // Act & Assert — the answer still points at this option.
        $this->assertDeletionIsBlocked($option, 'answers');
    }

    public function test_deleting_an_option_that_owns_ratings_is_blocked(): void
    {
        // Arrange
        $question = $this->createQuestion();
        $option = $this->createOption($question);
        $session = $this->createSession($question->assessment_version_id);

        $answer = Answer::create([
            'assessment_session_id' => $session->id,
            'question_id' => $question->id,
            'response_type' => 'option',
            'primary_option_id' => $option->id,
        ]);

        AnswerOptionRating::create([
            'answer_id' => $answer->id,
            'question_option_id' => $option->id,
            'rating' => 2,
        ]);

        // Act & Assert
        $this->assertDeletionIsBlocked($option, 'answer_option_ratings');
    }

    public function test_deleting_an_answer_that_owns_ratings_is_blocked(): void
    {
        // Arrange
        $question = $this->createQuestion();
        $option = $this->createOption($question);
        $session = $this->createSession($question->assessment_version_id);

        $answer = Answer::create([
            'assessment_session_id' => $session->id,
            'question_id' => $question->id,
            'response_type' => 'option',
            'primary_option_id' => $option->id,
        ]);

        AnswerOptionRating::create([
            'answer_id' => $answer->id,
            'question_option_id' => $option->id,
            'rating' => 1,
        ]);

        // Act & Assert
        $this->assertDeletionIsBlocked($answer, 'answer_option_ratings');
    }

    public function test_deleting_a_session_that_owns_a_result_is_blocked(): void
    {
        // Arrange
        $question = $this->createQuestion();
        $session = $this->createSession($question->assessment_version_id);

        Result::create([
            'assessment_session_id' => $session->id,
            'catalog_version' => '2026.1',
            'scoring_version' => '1.0',
        ]);

        // Act & Assert
        $this->assertDeletionIsBlocked($session, 'results');
    }

    public function test_deleting_a_result_that_owns_scores_is_blocked(): void
    {
        // Arrange
        $result = $this->createResult();

        ResultScore::create([
            'result_id' => $result->id,
            'riasec_code' => 'R',
            'score' => 55.50,
        ]);

        // Act & Assert
        $this->assertDeletionIsBlocked($result, 'result_scores');
    }

    public function test_deleting_a_result_that_owns_recommendations_is_blocked(): void
    {
        // Arrange
        $result = $this->createResult();

        ResultRecommendation::create([
            'result_id' => $result->id,
            'specialization_key' => 'software',
            'name_snapshot' => 'تطوير البرمجيات',
            'display_order' => 1,
            'similarity_score' => 80.00,
            'rationale_snapshot' => 'تقارب عالٍ',
        ]);

        // Act & Assert
        $this->assertDeletionIsBlocked($result, 'result_recommendations');
    }

    /**
     * @return array<string, array{string, array<string, mixed>, string}>
     */
    public static function orphanInsertProvider(): array
    {
        return [
            'question → unknown version' => [
                'questions',
                [
                    'assessment_version_id' => 999999,
                    'position' => 1,
                    'scenario' => 'يتيم',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                'assessment_versions',
            ],
            'option → unknown question' => [
                'question_options',
                [
                    'question_id' => 999999,
                    'position' => 1,
                    'option_text' => 'يتيم',
                    'riasec_code' => 'R',
                ],
                'questions',
            ],
            'session → unknown version' => [
                'assessment_sessions',
                [
                    'user_id' => 1,
                    'assessment_version_id' => 999999,
                    'started_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                'assessment_versions',
            ],
        ];
    }

    #[DataProvider('orphanInsertProvider')]
    public function test_inserting_a_child_with_an_unknown_parent_is_rejected(string $table, array $row, string $parentTable): void
    {
        // Arrange — the parent table is deliberately empty so the FK cannot resolve.
        $this->assertSame(0, DB::table($parentTable)->count(), "Precondition: {$parentTable} must be empty.");

        // Act — a raw insert bypasses every application-level guard.
        $rejected = false;
        try {
            DB::table($table)->insert($row);
        } catch (QueryException $e) {
            $rejected = true;
        }

        // Assert — the engine itself must refuse the orphan.
        $this->assertTrue($rejected, "An insert into {$table} with a missing parent must be rejected by the database.");
        $this->assertSame(0, DB::table($table)->count(), 'No orphan row may survive the rejected insert.');
    }

    public function test_inserting_an_answer_with_an_unknown_question_is_rejected(): void
    {
        // Arrange — a real session, but the question does not exist.
        $version = $this->createVersion();
        $session = $this->createSession($version->id);

        // Act
        $rejected = false;
        try {
            DB::table('answers')->insert([
                'assessment_session_id' => $session->id,
                'question_id' => 999999,
                'response_type' => 'none',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (QueryException $e) {
            $rejected = true;
        }

        // Assert
        $this->assertTrue($rejected, 'An answer pointing at a non-existent question must be rejected.');
        $this->assertSame(0, Answer::count());
    }

    /**
     * Assert the database refuses to delete a parent that still owns children.
     */
    private function assertDeletionIsBlocked(object $parent, string $childTable): void
    {
        $childrenBefore = DB::table($childTable)->count();

        try {
            $parent->delete();
            $deleted = true;
        } catch (QueryException $e) {
            $deleted = false;
        }

        $this->assertFalse(
            $deleted,
            "Deleting a parent that still owns rows in {$childTable} must be blocked by the foreign key."
        );

        $this->assertSame(
            $childrenBefore,
            DB::table($childTable)->count(),
            'No child row may be removed by the blocked deletion.'
        );
    }

    private function createVersion(): AssessmentVersion
    {
        return AssessmentVersion::create([
            'version_number' => 1,
            'status' => 'draft',
        ]);
    }

    private function createQuestion(): Question
    {
        return Question::create([
            'assessment_version_id' => $this->createVersion()->id,
            'position' => 1,
            'scenario' => 'موقف اختبار',
        ]);
    }

    private function createOption(Question $question): QuestionOption
    {
        return QuestionOption::create([
            'question_id' => $question->id,
            'position' => 1,
            'option_text' => 'خيار اختبار',
            'riasec_code' => 'R',
        ]);
    }

    private function createSession(int $versionId): AssessmentSession
    {
        return AssessmentSession::create([
            'user_id' => User::factory()->create()->id,
            'assessment_version_id' => $versionId,
            'started_at' => now(),
        ]);
    }

    private function createResult(): Result
    {
        $version = $this->createVersion();
        $session = $this->createSession($version->id);

        return Result::create([
            'assessment_session_id' => $session->id,
            'catalog_version' => '2026.1',
            'scoring_version' => '1.0',
        ]);
    }
}
