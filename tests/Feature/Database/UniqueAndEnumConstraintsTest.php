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
 * Q-02 (Phase 2) — Unique, enum and CHECK constraint enforcement.
 *
 * Every test drives a raw DB::table()->insert() or Model::create() straight at
 * the engine, with no request layer in between, so a pass proves the database
 * itself is the guard. Tests marked FD-x assert the *safe* behaviour required
 * by spec v1.2; if the migration lacks the constraint, they fail — and that
 * failure is the documented evidence of the defect.
 *
 * Production code is never modified by these tests.
 */
class UniqueAndEnumConstraintsTest extends TestCase
{
    use RefreshDatabase;

    public function test_duplicate_version_number_is_rejected(): void
    {
        // Arrange
        AssessmentVersion::create(['version_number' => 12, 'status' => 'active']);

        // Act & Assert — version numbers are globally unique.
        $this->assertRejected('assessment_versions', [
            'version_number' => 12,
            'status' => 'draft',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_duplicate_question_position_within_a_version_is_rejected(): void
    {
        // Arrange
        $version = $this->createVersion();
        Question::create([
            'assessment_version_id' => $version->id,
            'position' => 1,
            'scenario' => 'الأول',
        ]);

        // Act & Assert — unique (assessment_version_id, position).
        $this->assertRejected('questions', [
            'assessment_version_id' => $version->id,
            'position' => 1,
            'scenario' => 'مكرر',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_duplicate_option_position_within_a_question_is_rejected(): void
    {
        // Arrange
        $question = $this->createQuestion();
        QuestionOption::create([
            'question_id' => $question->id,
            'position' => 1,
            'option_text' => 'الأول',
            'riasec_code' => 'R',
        ]);

        // Act & Assert — unique (question_id, position).
        $this->assertRejected('question_options', [
            'question_id' => $question->id,
            'position' => 1,
            'option_text' => 'مكرر',
            'riasec_code' => 'I',
        ]);
    }

    public function test_second_answer_for_the_same_question_in_a_session_is_rejected(): void
    {
        // Arrange
        $session = $this->createSession();
        $question = $this->createQuestion($session->assessment_version_id);

        Answer::create([
            'assessment_session_id' => $session->id,
            'question_id' => $question->id,
            'response_type' => 'none',
        ]);

        // Act & Assert — one effective answer per question per session.
        $this->assertRejected('answers', [
            'assessment_session_id' => $session->id,
            'question_id' => $question->id,
            'response_type' => 'option',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_second_result_for_the_same_session_is_rejected(): void
    {
        // Arrange
        $session = $this->createSession();
        Result::create([
            'assessment_session_id' => $session->id,
            'catalog_version' => '2026.1',
            'scoring_version' => '1.0',
        ]);

        // Act & Assert — a session produces at most one result.
        $this->assertRejected('results', [
            'assessment_session_id' => $session->id,
            'catalog_version' => '2026.1',
            'scoring_version' => '1.0',
            'created_at' => now(),
        ]);
    }

    public function test_duplicate_riasec_score_within_a_result_is_rejected(): void
    {
        // Arrange
        $result = $this->createResult();
        ResultScore::create([
            'result_id' => $result->id,
            'riasec_code' => 'R',
            'score' => 55.50,
        ]);

        // Act & Assert — each domain appears once per result.
        $this->assertRejected('result_scores', [
            'result_id' => $result->id,
            'riasec_code' => 'R',
            'score' => 60.00,
        ]);
    }

    public function test_duplicate_recommendation_key_within_a_result_is_rejected(): void
    {
        // Arrange
        $result = $this->createResult();
        $this->createRecommendation($result, 'software', 1);

        // Act & Assert — unique (result_id, specialization_key).
        $this->assertRejected('result_recommendations', [
            'result_id' => $result->id,
            'specialization_key' => 'software',
            'name_snapshot' => 'تطوير البرمجيات',
            'display_order' => 2,
            'similarity_score' => 70.00,
            'rationale_snapshot' => 'تقارب',
        ]);
    }

    public function test_duplicate_recommendation_display_order_within_a_result_is_rejected(): void
    {
        // Arrange
        $result = $this->createResult();
        $this->createRecommendation($result, 'software', 1);

        // Act & Assert — unique (result_id, display_order).
        $this->assertRejected('result_recommendations', [
            'result_id' => $result->id,
            'specialization_key' => 'networks',
            'name_snapshot' => 'الشبكات',
            'display_order' => 1,
            'similarity_score' => 65.00,
            'rationale_snapshot' => 'تقارب',
        ]);
    }

    public function test_duplicate_rating_for_the_same_option_in_an_answer_is_rejected(): void
    {
        // Arrange
        $answer = $this->createAnswerWithOption();
        $option = $answer->primaryOption;

        AnswerOptionRating::create([
            'answer_id' => $answer->id,
            'question_option_id' => $option->id,
            'rating' => 2,
        ]);

        // Act & Assert — a student rates each option at most once per answer.
        $this->assertRejected('answer_option_ratings', [
            'answer_id' => $answer->id,
            'question_option_id' => $option->id,
            'rating' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_duplicate_user_email_is_rejected(): void
    {
        // Arrange
        User::factory()->create(['email' => 'student@example.com']);

        // Act & Assert
        $this->assertRejected('users', [
            'name' => 'آخر',
            'email' => 'student@example.com',
            'password' => 'hashed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_invalid_version_status_is_rejected(): void
    {
        // Arrange
        $version = $this->createVersion();

        // Act & Assert — only draft, active, retired are legal.
        $this->assertRejected('assessment_versions', [
            'id' => $version->id + 1,
            'version_number' => 999,
            'status' => 'published',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_invalid_riasec_code_is_rejected(): void
    {
        // Arrange
        $question = $this->createQuestion();

        // Act & Assert — spec v1.2 §30 allows only the six approved codes.
        $this->assertRejected('question_options', [
            'question_id' => $question->id,
            'position' => 1,
            'option_text' => 'خيار',
            'riasec_code' => 'X',
        ]);
    }

    public function test_invalid_response_type_is_rejected(): void
    {
        // Arrange
        $session = $this->createSession();
        $question = $this->createQuestion($session->assessment_version_id);

        // Act & Assert — spec v1.2 §30.1 defines the closed set.
        $this->assertRejected('answers', [
            'assessment_session_id' => $session->id,
            'question_id' => $question->id,
            'response_type' => 'closest',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_invalid_session_status_is_rejected(): void
    {
        // Arrange
        $version = $this->createVersion();

        // Act & Assert
        $this->assertRejected('assessment_sessions', [
            'user_id' => User::factory()->create()->id,
            'assessment_version_id' => $version->id,
            'status' => 'cancelled',
            'started_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_invalid_user_role_is_rejected(): void
    {
        // Act & Assert — the system has exactly two roles.
        $this->assertRejected('users', [
            'name' => 'دور غير صالح',
            'email' => 'role@example.com',
            'password' => 'hashed',
            'role' => 'teacher',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * The closed rating scale per spec v1.2 §31.
     *
     * @return array<string, array{int}>
     */
    public static function validRatingProvider(): array
    {
        return [
            '−2' => [-2],
            '−1' => [-1],
            '0' => [0],
            '+1' => [1],
            '+2' => [2],
        ];
    }

    #[DataProvider('validRatingProvider')]
    public function test_rating_accepts_every_value_of_the_approved_scale(int $rating): void
    {
        // Arrange
        $answer = $this->createAnswerWithOption();

        // Act
        AnswerOptionRating::create([
            'answer_id' => $answer->id,
            'question_option_id' => $answer->primaryOption->id,
            'rating' => $rating,
        ]);

        // Assert
        $this->assertDatabaseHas('answer_option_ratings', [
            'answer_id' => $answer->id,
            'question_option_id' => $answer->primaryOption->id,
            'rating' => $rating,
        ]);
    }

    /**
     * Values outside the closed scale must be refused by the CHECK constraint.
     *
     * @return array<string, array{int}>
     */
    public static function invalidRatingProvider(): array
    {
        return [
            '−3' => [-3],
            '+3' => [3],
            '127 (tinyint ceiling)' => [127],
        ];
    }

    #[DataProvider('invalidRatingProvider')]
    public function test_rating_rejects_values_outside_the_approved_scale(int $rating): void
    {
        // Arrange
        $answer = $this->createAnswerWithOption();

        // Act & Assert — the CHECK constraint is the guard, not the UI.
        $this->assertRejected('answer_option_ratings', [
            'answer_id' => $answer->id,
            'question_option_id' => $answer->primaryOption->id,
            'rating' => $rating,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * FD-2 (candidate defect) — spec v1.2/§4.3 fixes question positions at
     * 1..18. The migration declares an unsigned integer with no CHECK, so an
     * out-of-range value is currently accepted. This test asserts the safe
     * behaviour; a failure is the defect's proof, not a test to be loosened.
     *
     * @return array<string, array{int}>
     */
    public static function outOfRangeQuestionPositionProvider(): array
    {
        return [
            'zero' => [0],
            'nineteen' => [19],
        ];
    }

    #[DataProvider('outOfRangeQuestionPositionProvider')]
    public function test_question_position_outside_the_approved_range_is_rejected(int $position): void
    {
        // Arrange
        $version = $this->createVersion();

        // Act & Assert — the engine should refuse a position outside 1..18.
        $this->assertRejected('questions', [
            'assessment_version_id' => $version->id,
            'position' => $position,
            'scenario' => 'خارج النطاق',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * FD-2 (candidate defect) — option positions are fixed at 1..4.
     *
     * @return array<string, array{int}>
     */
    public static function outOfRangeOptionPositionProvider(): array
    {
        return [
            'zero' => [0],
            'five' => [5],
        ];
    }

    #[DataProvider('outOfRangeOptionPositionProvider')]
    public function test_option_position_outside_the_approved_range_is_rejected(int $position): void
    {
        // Arrange
        $question = $this->createQuestion();

        // Act & Assert — the engine should refuse a position outside 1..4.
        $this->assertRejected('question_options', [
            'question_id' => $question->id,
            'position' => $position,
            'option_text' => 'خارج النطاق',
            'riasec_code' => 'R',
        ]);
    }

    /**
     * FD-1 (candidate defect) — spec v1.2 §33 forbids a primary option when
     * the response is not "option". No CHECK ties the two columns together,
     * so the combination is currently stored.
     *
     * @return array<string, array{string}>
     */
    public static function inconsistentResponseProvider(): array
    {
        return [
            'none with a primary option' => ['none'],
            'cannot_judge with a primary option' => ['cannot_judge'],
        ];
    }

    #[DataProvider('inconsistentResponseProvider')]
    public function test_a_non_option_response_must_not_carry_a_primary_option(string $responseType): void
    {
        // Arrange
        $session = $this->createSession();
        $question = $this->createQuestion($session->assessment_version_id);
        $option = QuestionOption::create([
            'question_id' => $question->id,
            'position' => 1,
            'option_text' => 'خيار',
            'riasec_code' => 'R',
        ]);

        // Act & Assert — the engine should refuse the inconsistent pair.
        $this->assertRejected('answers', [
            'assessment_session_id' => $session->id,
            'question_id' => $question->id,
            'primary_option_id' => $option->id,
            'response_type' => $responseType,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * FD-3 (candidate defect) — the normalized score is defined as 0–100, but
     * decimal(5,2) alone admits up to 999.99.
     */
    public function test_score_above_one_hundred_is_rejected(): void
    {
        // Arrange
        $result = $this->createResult();

        // Act & Assert — the engine should refuse a score above the maximum.
        $this->assertRejected('result_scores', [
            'result_id' => $result->id,
            'riasec_code' => 'R',
            'score' => 999.99,
        ]);
    }

    /**
     * FD-3 (candidate defect) — display_order is defined as 1..5.
     */
    public function test_recommendation_display_order_outside_the_approved_range_is_rejected(): void
    {
        // Arrange
        $result = $this->createResult();

        // Act & Assert — the engine should refuse an out-of-range order.
        $this->assertRejected('result_recommendations', [
            'result_id' => $result->id,
            'specialization_key' => 'software',
            'name_snapshot' => 'تطوير البرمجيات',
            'display_order' => 999,
            'similarity_score' => 50.00,
            'rationale_snapshot' => 'خارج النطاق',
        ]);
    }

    /**
     * Assert the database refuses a row that violates a constraint.
     */
    private function assertRejected(string $table, array $row): void
    {
        // Arrange — the legitimate rows created for the scenario must not be
        // mistaken for a leaked violation, so the guard compares deltas.
        $before = DB::table($table)->count();

        // Act — bypass the request layer entirely.
        $rejected = false;
        try {
            DB::table($table)->insert($row);
        } catch (QueryException $e) {
            $rejected = true;
        }

        // Assert — the engine itself must be the guard, and nothing may leak.
        $this->assertTrue($rejected, "A violating row was accepted into {$table}.");
        $this->assertSame($before, DB::table($table)->count(), "No violating row may be persisted in {$table}.");
    }

    private function createVersion(): AssessmentVersion
    {
        return AssessmentVersion::create([
            'version_number' => mt_rand(100, 999),
            'status' => 'draft',
        ]);
    }

    private function createQuestion(?int $versionId = null): Question
    {
        return Question::create([
            'assessment_version_id' => $versionId ?? $this->createVersion()->id,
            'position' => mt_rand(1, 18),
            'scenario' => 'موقف اختبار',
        ]);
    }

    private function createSession(): AssessmentSession
    {
        $version = AssessmentVersion::create([
            'version_number' => mt_rand(1000, 9999),
            'status' => 'active',
        ]);

        return AssessmentSession::create([
            'user_id' => User::factory()->create()->id,
            'assessment_version_id' => $version->id,
            'started_at' => now(),
        ]);
    }

    private function createResult(): Result
    {
        return Result::create([
            'assessment_session_id' => $this->createSession()->id,
            'catalog_version' => '2026.1',
            'scoring_version' => '1.0',
        ]);
    }

    private function createRecommendation(Result $result, string $key, int $order): ResultRecommendation
    {
        return ResultRecommendation::create([
            'result_id' => $result->id,
            'specialization_key' => $key,
            'name_snapshot' => 'تطوير البرمجيات',
            'display_order' => $order,
            'similarity_score' => 80.00,
            'rationale_snapshot' => 'تقارب عالٍ',
        ]);
    }

    private function createAnswerWithOption(): Answer
    {
        $version = AssessmentVersion::create([
            'version_number' => mt_rand(10000, 99999),
            'status' => 'active',
        ]);
        $question = Question::create([
            'assessment_version_id' => $version->id,
            'position' => 1,
            'scenario' => 'موقف اختبار',
        ]);
        $option = QuestionOption::create([
            'question_id' => $question->id,
            'position' => 1,
            'option_text' => 'خيار اختبار',
            'riasec_code' => 'R',
        ]);
        $session = AssessmentSession::create([
            'user_id' => User::factory()->create()->id,
            'assessment_version_id' => $version->id,
            'started_at' => now(),
        ]);

        return Answer::create([
            'assessment_session_id' => $session->id,
            'question_id' => $question->id,
            'primary_option_id' => $option->id,
            'response_type' => 'option',
        ]);
    }
}
