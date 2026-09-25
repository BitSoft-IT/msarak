<?php

namespace Tests\Feature\AssessmentJourney;

use App\Models\Answer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\AssessmentJourneyFixtures;
use Tests\TestCase;

/**
 * Q-03 — Phase 3: a rejected save must look rejected, and the journey must be
 * recoverable: the student fixes the input and the same request succeeds.
 *
 * The "failure" here is a contract-level rejection (422 ANSWER_INVALID). The
 * transport-level failure path (500 ANSWER_SAVE_FAILED with rollback) is
 * already covered by:
 *   - tests/Feature/AssessmentSessionTest.php::test_failed_save_rolls_back_all_changes
 *   - tests/Feature/AssessmentSessionTest.php::test_failed_update_preserves_previous_successful_state
 *   - tests/Feature/AssessmentSessionTest.php::test_failed_save_does_not_change_progress
 * so this file adds only the retry semantics and the response-shape guarantee.
 */
class AnswerFailureRecoveryTest extends TestCase
{
    use RefreshDatabase;
    use AssessmentJourneyFixtures;

    #[DataProvider('invalidPayloadProvider')]
    public function test_retry_after_validation_failure_succeeds_and_stores_the_answer(array $invalidPayload): void
    {
        // Arrange
        $student = $this->createStudentUser();
        $version = $this->createPublishedAssessmentVersion(1);
        $session = $this->createInProgressSession($student, $version);
        $question = $version->questions()->where('position', 1)->first();
        $options = $question->questionOptions;

        $validPayload = $this->createPrimaryAnswerPayload($options[0]->id, [
            ['option_id' => $options[0]->id, 'rating' => 2],
            ['option_id' => $options[1]->id, 'rating' => 1],
        ]);

        // Previous State: nothing is stored yet
        $this->assertSame(0, Answer::where('assessment_session_id', $session->id)->count());

        // Act 1: the first attempt is rejected
        $rejected = $this->actingAs($student)
            ->putJson(route('assessment.answers.update', [
                'assessmentSession' => $session->id,
                'question' => $question->id,
            ]), $invalidPayload);

        // Assert 1 (HTTP): a clear rejection, never a silent success
        $rejected->assertStatus(422);
        $this->assertSame('ANSWER_INVALID', $rejected->json('code'));
        $this->assertArrayNotHasKey('data', $rejected->json(), 'الرفض يجب ألا يحمل حمولة نجاح.');

        // Assert 1 (DB): the rejection stored nothing
        $this->assertSame(0, Answer::where('assessment_session_id', $session->id)->count());

        // Act 2: the student corrects the input and retries the same question
        $retry = $this->actingAs($student)
            ->putJson(route('assessment.answers.update', [
                'assessmentSession' => $session->id,
                'question' => $question->id,
            ]), $validPayload);

        // Assert 2 (HTTP): the retry is accepted and reports real progress
        $retry->assertStatus(200);
        $this->assertTrue($retry->json('data.saved'), 'إعادة المحاولة يجب أن تنجح.');
        $this->assertSame(1, $retry->json('data.progress.processed'));
        $this->assertSame($options[0]->id, $retry->json('data.answer.primary_option_id'));

        // Assert 2 (DB): exactly one answer, exactly the retried ratings, no duplicates
        $this->assertSame(1, Answer::where('assessment_session_id', $session->id)->count(), 'لا تكرار في الإجابات.');
        $answer = Answer::where('assessment_session_id', $session->id)->firstOrFail();
        $this->assertSame($options[0]->id, $answer->primary_option_id);
        $this->assertSame(
            [$options[0]->id => 2, $options[1]->id => 1],
            $this->captureRatingsSnapshot($answer),
            'التقييمات المخزنة هي تقييمات الطلب الناجح فقط.'
        );
        $this->assertSame(2, $answer->optionRatings()->count());
    }

    public function test_a_failed_save_response_never_reports_success_or_progress(): void
    {
        // Arrange
        $student = $this->createStudentUser();
        $version = $this->createPublishedAssessmentVersion(1);
        $session = $this->createInProgressSession($student, $version);
        $question = $version->questions()->where('position', 1)->first();
        $options = $question->questionOptions;

        // Previous State
        $this->assertSame(0, Answer::where('assessment_session_id', $session->id)->count());

        // Act: an out-of-range rating is rejected by the contract
        $response = $this->actingAs($student)
            ->putJson(route('assessment.answers.update', [
                'assessmentSession' => $session->id,
                'question' => $question->id,
            ]), $this->createPrimaryAnswerPayload($options[0]->id, [
                ['option_id' => $options[0]->id, 'rating' => 9],
            ]));

        // Assert (HTTP): the body is an error envelope, nothing else
        $response->assertStatus(422);
        $body = $response->json();
        $this->assertArrayNotHasKey('data', $body);
        $this->assertArrayNotHasKey('saved', $body);
        $this->assertArrayNotHasKey('progress', $body);
        $this->assertSame('ANSWER_INVALID', $body['code']);
        $this->assertNotEmpty($body['message'], 'رسالة الخطأ يجب ألا تكون فارغة.');
        $this->assertNotEmpty($body['errors'], 'تفاصيل الخطأ يجب أن تُعرض للمستخدم.');

        // Assert (DB): progress untouched
        $this->assertSame(0, Answer::where('assessment_session_id', $session->id)->count());
    }

    /**
     * Two distinct contract violations, each exercising the retry path.
     *
     * Option ids are the first question's options: with RefreshDatabase the
     * bank starts at id 1, so 1 and 2 are always valid members of the question.
     *
     * @return array<string, array{0: array}>
     */
    public static function invalidPayloadProvider(): array
    {
        return [
            'تقييم خارج النطاق' => [
                [
                    'primary_option_id' => 1,
                    'none_selected' => false,
                    'unable_to_judge' => false,
                    'ratings' => [
                        ['option_id' => 1, 'rating' => 9],
                    ],
                ],
            ],
            'تعارض حالات الإجابة' => [
                [
                    'primary_option_id' => 1,
                    'none_selected' => true,
                    'unable_to_judge' => false,
                    'ratings' => [],
                ],
            ],
        ];
    }
}
