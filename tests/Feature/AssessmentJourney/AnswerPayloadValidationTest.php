<?php

namespace Tests\Feature\AssessmentJourney;

use App\Models\Answer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\AssessmentJourneyFixtures;
use Tests\TestCase;

/**
 * Q-03 — Phase 2: the JSON shape and field types of the save-answer payload.
 *
 * Only the cases that protect the H-03 integration contract or expose a
 * boundary behaviour of this project's validation path are included. The
 * out-of-range rating values are covered by AnswerRatingsSemanticsTest and by
 * the database-integrity suite.
 *
 * NOTE: `test_a_non_object_rating_element_is_rejected` pins the contract
 * expectation (422 ANSWER_INVALID). Laravel's implicit `required_with` rule
 * resolves `ratings.0.option_id` / `ratings.0.rating` even for a non-array
 * element, so the malformed payload is rejected at the validation layer and
 * never reaches the service — the behaviour matches H-03.
 */
class AnswerPayloadValidationTest extends TestCase
{
    use RefreshDatabase;
    use AssessmentJourneyFixtures;

    /**
     * @return array<string, array{0: mixed}>
     */
    public static function nonIntegerPrimaryOptionProvider(): array
    {
        return [
            'non numeric string' => ['abc'],
            'decimal value' => [1.5],
        ];
    }

    #[DataProvider('nonIntegerPrimaryOptionProvider')]
    public function test_primary_option_id_accepts_only_integer_like_values(mixed $primaryOptionId): void
    {
        // Arrange
        $student = $this->createStudentUser();
        $version = $this->createPublishedAssessmentVersion(1);
        $session = $this->createInProgressSession($student, $version);
        $question = $version->questions()->where('position', 1)->first();

        // Act
        $response = $this->actingAs($student)
            ->putJson(route('assessment.answers.update', [
                'assessmentSession' => $session->id,
                'question' => $question->id,
            ]), [
                'primary_option_id' => $primaryOptionId,
                'none_selected' => false,
                'unable_to_judge' => false,
                'ratings' => [],
            ]);

        // Assert (HTTP)
        $this->assertSame(422, $response->getStatusCode(), 'المعرّف غير الصحيح النوع يجب أن يُرفض.');
        $response->assertJson([
            'message' => 'بيانات الإجابة غير صالحة.',
            'code' => 'ANSWER_INVALID',
        ]);

        // Assert (DB)
        $this->assertSame(0, Answer::where('assessment_session_id', $session->id)->count());
    }

    public function test_ratings_must_be_a_list(): void
    {
        // Arrange
        $student = $this->createStudentUser();
        $version = $this->createPublishedAssessmentVersion(1);
        $session = $this->createInProgressSession($student, $version);
        $question = $version->questions()->where('position', 1)->first();
        $option = $question->questionOptions()->first();

        // Act: ratings is a scalar instead of an array
        $response = $this->actingAs($student)
            ->putJson(route('assessment.answers.update', [
                'assessmentSession' => $session->id,
                'question' => $question->id,
            ]), [
                'primary_option_id' => $option->id,
                'none_selected' => false,
                'unable_to_judge' => false,
                'ratings' => 'not-a-list',
            ]);

        // Assert (HTTP)
        $this->assertSame(422, $response->getStatusCode(), 'التقييمات غير المصفوفية يجب أن تُرفض.');
        $response->assertJson([
            'message' => 'بيانات الإجابة غير صالحة.',
            'code' => 'ANSWER_INVALID',
        ]);

        // Assert (DB)
        $this->assertSame(0, Answer::where('assessment_session_id', $session->id)->count());
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function incompleteRatingElementProvider(): array
    {
        return [
            'element without option_id' => ['option_id'],
            'element without rating' => ['rating'],
        ];
    }

    #[DataProvider('incompleteRatingElementProvider')]
    public function test_rating_elements_must_carry_both_option_id_and_rating(string $missingField): void
    {
        // Arrange
        $student = $this->createStudentUser();
        $version = $this->createPublishedAssessmentVersion(1);
        $session = $this->createInProgressSession($student, $version);
        $question = $version->questions()->where('position', 1)->first();
        $option = $question->questionOptions()->first();

        // An element that genuinely lacks one of the two contract fields
        $element = match ($missingField) {
            'option_id' => ['rating' => 2],
            'rating' => ['option_id' => $option->id],
        };

        // Act
        $response = $this->actingAs($student)
            ->putJson(route('assessment.answers.update', [
                'assessmentSession' => $session->id,
                'question' => $question->id,
            ]), $this->createPrimaryAnswerPayload($option->id, [$element]));

        // Assert (HTTP)
        $this->assertSame(422, $response->getStatusCode(), 'عنصر التقييم الناقص حقلًا يجب أن يُرفض.');
        $response->assertJson([
            'message' => 'بيانات الإجابة غير صالحة.',
            'code' => 'ANSWER_INVALID',
        ]);

        // Assert (DB)
        $this->assertSame(0, Answer::where('assessment_session_id', $session->id)->count());
    }

    /**
     * @return array<string, array{0: mixed}>
     */
    public static function emptyRatingsProvider(): array
    {
        return [
            'ratings key absent' => [null],
            'ratings as empty list' => [[]],
        ];
    }

    #[DataProvider('emptyRatingsProvider')]
    public function test_absent_and_empty_ratings_are_both_accepted(mixed $ratings): void
    {
        // Arrange
        $student = $this->createStudentUser();
        $version = $this->createPublishedAssessmentVersion(1);
        $session = $this->createInProgressSession($student, $version);
        $question = $version->questions()->where('position', 1)->first();
        $option = $question->questionOptions()->first();

        // Act: H-03 allows ratings to be "غائبة أو فارغة"
        $payload = $this->createPrimaryAnswerPayload($option->id);
        $payload['ratings'] = $ratings;

        $response = $this->actingAs($student)
            ->putJson(route('assessment.answers.update', [
                'assessmentSession' => $session->id,
                'question' => $question->id,
            ]), $payload);

        // Assert (HTTP)
        $response->assertStatus(200);
        $this->assertTrue($response->json('data.saved'));
        $this->assertSame([], $response->json('data.answer.ratings'), 'لا يجب أن توجد تقييمات.');

        // Assert (DB): the answer exists with no rating rows
        $answer = Answer::where('assessment_session_id', $session->id)->firstOrFail();
        $this->assertSame($option->id, $answer->primary_option_id);
        $this->assertSame(0, $answer->optionRatings()->count());
    }

    public function test_a_non_object_rating_element_is_rejected(): void
    {
        // Arrange
        $student = $this->createStudentUser();
        $version = $this->createPublishedAssessmentVersion(1);
        $session = $this->createInProgressSession($student, $version);
        $question = $version->questions()->where('position', 1)->first();
        $option = $question->questionOptions()->first();

        // Act: a ratings list carrying a non-object element
        $response = $this->actingAs($student)
            ->putJson(route('assessment.answers.update', [
                'assessmentSession' => $session->id,
                'question' => $question->id,
            ]), $this->createPrimaryAnswerPayload($option->id, ['not-an-object']));

        // Assert (HTTP): a malformed element must be a validation error, never a crash
        $this->assertSame(422, $response->getStatusCode(), 'عنصر التقييم غير الكائن يجب أن يعيد 422 ANSWER_INVALID.');
        $response->assertJson([
            'message' => 'بيانات الإجابة غير صالحة.',
            'code' => 'ANSWER_INVALID',
        ]);

        // Assert (DB)
        $this->assertSame(0, Answer::where('assessment_session_id', $session->id)->count());
    }
}
