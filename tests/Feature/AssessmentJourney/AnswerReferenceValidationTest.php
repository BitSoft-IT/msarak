<?php

namespace Tests\Feature\AssessmentJourney;

use App\Models\Answer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\AssessmentJourneyFixtures;
use Tests\TestCase;

/**
 * Q-03 — Phase 2: the session / question / option reference rules of the H-03
 * answer contract, and the shape of the failure when the routed question does
 * not exist.
 *
 * A foreign option in the primary pick or in the ratings, and a question from
 * another version, are already covered by tests/Feature/AssessmentSessionTest.php
 * (test_option_from_another_question_is_rejected,
 *  test_option_from_another_question_rejected_in_ratings,
 *  test_question_from_another_version_is_rejected). This file covers the
 * reference gaps: non-existent ids, the identical-value duplicate, the mirrored
 * version direction, route-model binding, and previous-state preservation.
 */
class AnswerReferenceValidationTest extends TestCase
{
    use RefreshDatabase;
    use AssessmentJourneyFixtures;

    /**
     * @return array<string, array{0: bool}>
     */
    public static function unknownOptionProvider(): array
    {
        return [
            'unknown primary option id' => [true],
            'unknown rating option id' => [false],
        ];
    }

    #[DataProvider('unknownOptionProvider')]
    public function test_unknown_option_ids_are_rejected(bool $inPrimary): void
    {
        // Arrange
        $student = $this->createStudentUser();
        $version = $this->createPublishedAssessmentVersion(1);
        $session = $this->createInProgressSession($student, $version);
        $question = $version->questions()->where('position', 1)->first();
        $option = $question->questionOptions()->first();
        $unknownOptionId = 999999;

        // Act
        $payload = $inPrimary
            ? $this->createPrimaryAnswerPayload($unknownOptionId)
            : $this->createPrimaryAnswerPayload($option->id, [
                ['option_id' => $unknownOptionId, 'rating' => 1],
            ]);

        $response = $this->actingAs($student)
            ->putJson(route('assessment.answers.update', [
                'assessmentSession' => $session->id,
                'question' => $question->id,
            ]), $payload);

        // Assert (HTTP)
        $this->assertSame(422, $response->getStatusCode(), 'المعرّف غير الموجود يجب أن يُرفض.');
        $response->assertJson([
            'message' => 'بيانات الإجابة غير صالحة.',
            'code' => 'ANSWER_INVALID',
        ]);

        // Assert (DB)
        $this->assertSame(0, Answer::where('assessment_session_id', $session->id)->count(), 'لا يجب أن تُحفظ أي إجابة.');
        $this->assertDatabaseCount('answer_option_ratings', 0);
    }

    public function test_duplicate_option_with_an_identical_rating_is_rejected(): void
    {
        // Arrange
        $student = $this->createStudentUser();
        $version = $this->createPublishedAssessmentVersion(1);
        $session = $this->createInProgressSession($student, $version);
        $question = $version->questions()->where('position', 1)->first();
        $option = $question->questionOptions()->first();

        // Act: the same option is rated twice with the same value
        $response = $this->actingAs($student)
            ->putJson(route('assessment.answers.update', [
                'assessmentSession' => $session->id,
                'question' => $question->id,
            ]), $this->createPrimaryAnswerPayload($option->id, [
                ['option_id' => $option->id, 'rating' => 1],
                ['option_id' => $option->id, 'rating' => 1],
            ]));

        // Assert (HTTP)
        $this->assertSame(422, $response->getStatusCode(), 'تكرار الخيار نفسه يجب أن يُرفض وإن تطابقت القيم.');
        $response->assertJson([
            'message' => 'بيانات الإجابة غير صالحة.',
            'code' => 'ANSWER_INVALID',
        ]);

        // Assert (DB)
        $this->assertSame(0, Answer::where('assessment_session_id', $session->id)->count());
        $this->assertDatabaseCount('answer_option_ratings', 0);
    }

    public function test_question_from_a_previous_version_is_rejected_for_a_session_on_a_newer_version(): void
    {
        // Arrange: an explicit version scenario — session on V2, question from V1
        $student = $this->createStudentUser();
        $v1 = $this->createPublishedAssessmentVersion(1);
        $v2 = $this->createPublishedAssessmentVersion(2);
        $session = $this->createInProgressSession($student, $v2);

        $questionV1 = $v1->questions()->where('position', 1)->first();
        $optionV1 = $questionV1->questionOptions()->first();

        // Act: the question belongs to the previous version, not the session's
        $response = $this->actingAs($student)
            ->putJson(route('assessment.answers.update', [
                'assessmentSession' => $session->id,
                'question' => $questionV1->id,
            ]), $this->createPrimaryAnswerPayload($optionV1->id));

        // Assert (HTTP)
        $this->assertSame(422, $response->getStatusCode(), 'سؤال إصدار أقدم يجب أن يُرفض لجلسة على إصدار أحدث.');
        $response->assertJson([
            'message' => 'بيانات الإجابة غير صالحة.',
            'code' => 'ANSWER_INVALID',
        ]);

        // Assert (DB): nothing was saved into the V2 session
        $this->assertSame(0, Answer::where('assessment_session_id', $session->id)->count());
    }

    public function test_missing_question_in_the_url_returns_resource_not_found(): void
    {
        // Arrange
        $student = $this->createStudentUser();
        $version = $this->createPublishedAssessmentVersion(1);
        $session = $this->createInProgressSession($student, $version);
        $option = $version->questions()->first()->questionOptions()->first();

        // Act: the routed question does not exist — route model binding fails
        $response = $this->actingAs($student)
            ->putJson(route('assessment.answers.update', [
                'assessmentSession' => $session->id,
                'question' => 999999,
            ]), $this->createPrimaryAnswerPayload($option->id));

        // Assert (HTTP): the JSON not-found envelope, not a raw framework 404
        $this->assertSame(404, $response->getStatusCode(), 'السؤال غير الموجود يجب أن يعيد 404.');
        $response->assertJson([
            'message' => 'المورد غير موجود.',
            'code' => 'RESOURCE_NOT_FOUND',
        ]);

        // Assert (DB)
        $this->assertSame(0, Answer::where('assessment_session_id', $session->id)->count());
    }

    public function test_reference_rejection_preserves_the_previous_answer_and_progress(): void
    {
        // Arrange
        $student = $this->createStudentUser();
        $version = $this->createPublishedAssessmentVersion(1);
        $session = $this->createInProgressSession($student, $version);
        $question = $version->questions()->where('position', 1)->first();
        $options = $question->questionOptions;

        // Previous State (through the real endpoint)
        $first = $this->actingAs($student)
            ->putJson(route('assessment.answers.update', [
                'assessmentSession' => $session->id,
                'question' => $question->id,
            ]), $this->createPrimaryAnswerPayload($options[0]->id, [
                ['option_id' => $options[0]->id, 'rating' => 2],
                ['option_id' => $options[1]->id, 'rating' => 1],
            ]));
        $first->assertStatus(200);

        $answer = Answer::where('assessment_session_id', $session->id)->firstOrFail();
        $previousAnswerId = $answer->id;
        $previousRatings = $this->captureRatingsSnapshot($answer);
        $previousProcessed = $first->json('data.progress.processed');

        // Act: an unknown option id in the ratings
        $response = $this->actingAs($student)
            ->putJson(route('assessment.answers.update', [
                'assessmentSession' => $session->id,
                'question' => $question->id,
            ]), $this->createPrimaryAnswerPayload($options[2]->id, [
                ['option_id' => 999999, 'rating' => 1],
            ]));

        // Assert (HTTP)
        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'بيانات الإجابة غير صالحة.',
            'code' => 'ANSWER_INVALID',
        ]);

        // Assert (DB + Previous State)
        $this->assertSame(1, Answer::where('assessment_session_id', $session->id)->count());
        $answer->refresh();
        $this->assertSame($previousAnswerId, $answer->id, 'معرّف الإجابة يجب ألا يتغير.');
        $this->assertSame($options[0]->id, $answer->primary_option_id, 'الاختيار الأساسي السابق يجب أن يبقى.');
        $this->assertSame($previousRatings, $this->captureRatingsSnapshot($answer), 'التقييمات السابقة يجب ألا تتغير.');

        $resume = $this->actingAs($student)
            ->getJson(route('assessment.show', $session->id));
        $resume->assertStatus(200);
        $this->assertSame($previousProcessed, $resume->json('data.progress.processed'), 'التقدم يجب ألا يتغير بعد الرفض.');
    }
}
