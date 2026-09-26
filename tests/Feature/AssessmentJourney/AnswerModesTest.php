<?php

namespace Tests\Feature\AssessmentJourney;

use App\Models\Answer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\AssessmentJourneyFixtures;
use Tests\TestCase;

/**
 * Q-03 — Phase 2: the "exactly one response state" rule of the H-03 answer
 * contract, and the preservation of a previously saved answer when a request
 * violates that rule.
 *
 * The valid primary / none_selected / unable_to_judge happy paths and the
 * "unable_to_judge + primary_option_id" and "unable_to_judge + ratings"
 * rejections are already covered by tests/Feature/AssessmentSessionTest.php,
 * so this file targets the state combinations that are not.
 */
class AnswerModesTest extends TestCase
{
    use RefreshDatabase;
    use AssessmentJourneyFixtures;

    /**
     * @return array<string, array{payload: array<string, mixed>}>
     */
    public static function noActiveStateProvider(): array
    {
        return [
            'empty body' => [
                'payload' => [],
            ],
            'all flags explicitly false' => [
                'payload' => [
                    'primary_option_id' => null,
                    'none_selected' => false,
                    'unable_to_judge' => false,
                    'ratings' => [],
                ],
            ],
        ];
    }

    #[DataProvider('noActiveStateProvider')]
    public function test_a_request_without_an_active_response_state_is_rejected(array $payload): void
    {
        // Arrange
        $student = $this->createStudentUser();
        $version = $this->createPublishedAssessmentVersion(1);
        $session = $this->createInProgressSession($student, $version);
        $question = $version->questions()->where('position', 1)->first();

        // Act: no response state is active at all
        $response = $this->actingAs($student)
            ->putJson(route('assessment.answers.update', [
                'assessmentSession' => $session->id,
                'question' => $question->id,
            ]), $payload);

        // Assert (HTTP)
        $this->assertSame(422, $response->getStatusCode(), 'غياب أي حالة إجابة يجب أن يعيد 422.');
        $response->assertJson([
            'message' => 'بيانات الإجابة غير صالحة.',
            'code' => 'ANSWER_INVALID',
        ]);

        // Assert (DB): nothing was materialised
        $this->assertSame(0, Answer::where('assessment_session_id', $session->id)->count(), 'لا يجب أن تُحفظ أي إجابة.');
        $this->assertDatabaseCount('answer_option_ratings', 0);
    }

    /**
     * @return array<string, array{0: bool, 1: bool, 2: bool}>
     */
    public static function conflictingStatesProvider(): array
    {
        return [
            // primary_option_id + none_selected
            'primary option with none selected' => [true, true, false],
            // none_selected + unable_to_judge
            'none selected with unable to judge' => [false, true, true],
            // all three states in the same request
            'all three states at once' => [true, true, true],
        ];
    }

    #[DataProvider('conflictingStatesProvider')]
    public function test_conflicting_response_states_are_rejected(bool $withPrimary, bool $noneSelected, bool $unableToJudge): void
    {
        // Arrange
        $student = $this->createStudentUser();
        $version = $this->createPublishedAssessmentVersion(1);
        $session = $this->createInProgressSession($student, $version);
        $question = $version->questions()->where('position', 1)->first();
        $option = $question->questionOptions()->first();

        // Act: two or three response states are active in the same request
        $response = $this->actingAs($student)
            ->putJson(route('assessment.answers.update', [
                'assessmentSession' => $session->id,
                'question' => $question->id,
            ]), [
                'primary_option_id' => $withPrimary ? $option->id : null,
                'none_selected' => $noneSelected,
                'unable_to_judge' => $unableToJudge,
                'ratings' => [],
            ]);

        // Assert (HTTP)
        $this->assertSame(422, $response->getStatusCode(), 'الجمع بين أكثر من حالة إجابة يجب أن يعيد 422.');
        $response->assertJson([
            'message' => 'بيانات الإجابة غير صالحة.',
            'code' => 'ANSWER_INVALID',
        ]);

        // Assert (DB)
        $this->assertSame(0, Answer::where('assessment_session_id', $session->id)->count(), 'لا يجب أن تُحفظ أي إجابة.');
        $this->assertDatabaseCount('answer_option_ratings', 0);
    }

    public function test_mode_conflict_rejection_preserves_the_previous_answer(): void
    {
        // Arrange
        $student = $this->createStudentUser();
        $version = $this->createPublishedAssessmentVersion(1);
        $session = $this->createInProgressSession($student, $version);
        $question = $version->questions()->where('position', 1)->first();
        $options = $question->questionOptions;

        // Previous State (built through the real endpoint, never a DB insert)
        $first = $this->actingAs($student)
            ->putJson(route('assessment.answers.update', [
                'assessmentSession' => $session->id,
                'question' => $question->id,
            ]), $this->createPrimaryAnswerPayload($options[0]->id, [
                ['option_id' => $options[0]->id, 'rating' => 2],
                ['option_id' => $options[1]->id, 'rating' => -1],
            ]));
        $first->assertStatus(200);

        $answer = Answer::where('assessment_session_id', $session->id)->firstOrFail();
        $previousAnswerId = $answer->id;
        $previousRatings = $this->captureRatingsSnapshot($answer);
        $previousProcessed = $first->json('data.progress.processed');

        // Act: a request that activates two response states at once
        $conflict = $this->actingAs($student)
            ->putJson(route('assessment.answers.update', [
                'assessmentSession' => $session->id,
                'question' => $question->id,
            ]), [
                'primary_option_id' => $options[2]->id,
                'none_selected' => true,
                'unable_to_judge' => false,
                'ratings' => [],
            ]);

        // Assert (HTTP)
        $conflict->assertStatus(422);
        $conflict->assertJson([
            'message' => 'بيانات الإجابة غير صالحة.',
            'code' => 'ANSWER_INVALID',
        ]);

        // Assert (DB + Previous State): one answer, same row, identical ratings
        $this->assertSame(1, Answer::where('assessment_session_id', $session->id)->count(), 'لا يجب أن يُنشأ سجل إجابة إضافي.');
        $answer->refresh();
        $this->assertSame($previousAnswerId, $answer->id, 'معرّف الإجابة يجب ألا يتغير.');
        $this->assertSame($options[0]->id, $answer->primary_option_id, 'الاختيار الأساسي السابق يجب أن يبقى.');
        $this->assertSame('option', $answer->response_type, 'نوع الاستجابة السابق يجب أن يبقى.');
        $this->assertSame($previousRatings, $this->captureRatingsSnapshot($answer), 'التقييمات السابقة يجب ألا تتغير.');

        // Assert (progress): read back through the contract read endpoint
        $resume = $this->actingAs($student)
            ->getJson(route('assessment.show', $session->id));
        $resume->assertStatus(200);
        $this->assertSame($previousProcessed, $resume->json('data.progress.processed'), 'التقدم يجب ألا يتغير بعد الرفض.');
    }
}
