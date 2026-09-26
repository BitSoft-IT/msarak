<?php

namespace Tests\Feature\AssessmentJourney;

use App\Models\Answer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\AssessmentJourneyFixtures;
use Tests\TestCase;

/**
 * Q-03 — Phase 2: the storage semantics of the optional -2..2 rating scale.
 *
 * The contract distinguishes an absent rating ("the student did not rate this
 * option") from an explicit rating = 0 ("neutral / unsure"). AssessmentSessionTest
 * already covers the -3/+3 rejections and the "rate one option, leave the rest
 * unrated" case; this file pins the full scale, the NULL-vs-0 distinction inside
 * a single answer, both zero transitions, and the remaining invalid value types.
 */
class AnswerRatingsSemanticsTest extends TestCase
{
    use RefreshDatabase;
    use AssessmentJourneyFixtures;

    /**
     * @return array<string, array{0: int}>
     */
    public static function approvedRatingScaleProvider(): array
    {
        return [
            'minimum rating -2' => [-2],
            'neutral rating 0' => [0],
            'maximum rating 2' => [2],
        ];
    }

    #[DataProvider('approvedRatingScaleProvider')]
    public function test_every_approved_rating_value_is_stored_and_echoed(int $rating): void
    {
        // Arrange
        $student = $this->createStudentUser();
        $version = $this->createPublishedAssessmentVersion(1);
        $session = $this->createInProgressSession($student, $version);
        $question = $version->questions()->where('position', 1)->first();
        $option = $question->questionOptions()->first();

        // Act
        $response = $this->actingAs($student)
            ->putJson(route('assessment.answers.update', [
                'assessmentSession' => $session->id,
                'question' => $question->id,
            ]), $this->createPrimaryAnswerPayload($option->id, [
                ['option_id' => $option->id, 'rating' => $rating],
            ]));

        // Assert (HTTP): the save response echoes the exact rating value
        $response->assertStatus(200);
        $echoedRatings = $response->json('data.answer.ratings');
        $this->assertCount(1, $echoedRatings, 'يجب أن يُعاد تقييم واحد فقط.');
        $this->assertSame($option->id, $echoedRatings[0]['option_id']);
        $this->assertSame($rating, $echoedRatings[0]['rating'], 'القيمة المُعادة يجب أن تطابق القيمة المُرسلة.');

        // Assert (DB): a real row carries that value
        $answer = Answer::where('assessment_session_id', $session->id)->firstOrFail();
        $this->assertDatabaseHas('answer_option_ratings', [
            'answer_id' => $answer->id,
            'question_option_id' => $option->id,
            'rating' => $rating,
        ]);
    }

    public function test_explicit_zero_and_a_missing_rating_coexist_within_one_answer(): void
    {
        // Arrange
        $student = $this->createStudentUser();
        $version = $this->createPublishedAssessmentVersion(1);
        $session = $this->createInProgressSession($student, $version);
        $question = $version->questions()->where('position', 1)->first();
        $options = $question->questionOptions;

        // Act: rate the first option explicitly as 0 and leave the second unrated
        $response = $this->actingAs($student)
            ->putJson(route('assessment.answers.update', [
                'assessmentSession' => $session->id,
                'question' => $question->id,
            ]), $this->createPrimaryAnswerPayload($options[0]->id, [
                ['option_id' => $options[0]->id, 'rating' => 0],
            ]));

        // Assert (HTTP)
        $response->assertStatus(200);
        $echoedRatings = $response->json('data.answer.ratings');
        $this->assertCount(1, $echoedRatings, 'يجب أن يُعاد تقييم واحد فقط.');
        $this->assertSame(0, $echoedRatings[0]['rating'], 'التقييم الصريح بصفر يجب أن يُعاد بصفر.');

        // Assert (DB): 0 is a stored row, absence is a missing row — not the same thing
        $answer = Answer::where('assessment_session_id', $session->id)->firstOrFail();
        $this->assertDatabaseHas('answer_option_ratings', [
            'answer_id' => $answer->id,
            'question_option_id' => $options[0]->id,
            'rating' => 0,
        ]);
        $this->assertDatabaseMissing('answer_option_ratings', [
            'answer_id' => $answer->id,
            'question_option_id' => $options[1]->id,
        ]);
    }

    public function test_a_rating_can_be_removed_by_omitting_the_option(): void
    {
        // Arrange
        $student = $this->createStudentUser();
        $version = $this->createPublishedAssessmentVersion(1);
        $session = $this->createInProgressSession($student, $version);
        $question = $version->questions()->where('position', 1)->first();
        $options = $question->questionOptions;

        // Previous State: two ratings, including an explicit 0
        $this->actingAs($student)
            ->putJson(route('assessment.answers.update', [
                'assessmentSession' => $session->id,
                'question' => $question->id,
            ]), $this->createPrimaryAnswerPayload($options[0]->id, [
                ['option_id' => $options[0]->id, 'rating' => 0],
                ['option_id' => $options[1]->id, 'rating' => 2],
            ]))->assertStatus(200);

        $answer = Answer::where('assessment_session_id', $session->id)->firstOrFail();
        $this->assertSame(2, $answer->optionRatings()->count());

        // Act: the second save keeps the primary but drops the zero-rated option
        $response = $this->actingAs($student)
            ->putJson(route('assessment.answers.update', [
                'assessmentSession' => $session->id,
                'question' => $question->id,
            ]), $this->createPrimaryAnswerPayload($options[0]->id, [
                ['option_id' => $options[1]->id, 'rating' => 2],
            ]));

        // Assert (HTTP + DB)
        $response->assertStatus(200);
        $this->assertSame(1, $answer->optionRatings()->count(), 'التقييم المحذوف من الطلب يجب ألا يبقى.');
        $this->assertDatabaseMissing('answer_option_ratings', [
            'answer_id' => $answer->id,
            'question_option_id' => $options[0]->id,
        ]);
        $this->assertDatabaseHas('answer_option_ratings', [
            'answer_id' => $answer->id,
            'question_option_id' => $options[1]->id,
            'rating' => 2,
        ]);
    }

    public function test_an_unrated_option_can_be_rated_zero(): void
    {
        // Arrange
        $student = $this->createStudentUser();
        $version = $this->createPublishedAssessmentVersion(1);
        $session = $this->createInProgressSession($student, $version);
        $question = $version->questions()->where('position', 1)->first();
        $options = $question->questionOptions;

        // Previous State: a primary pick with no ratings at all
        $this->actingAs($student)
            ->putJson(route('assessment.answers.update', [
                'assessmentSession' => $session->id,
                'question' => $question->id,
            ]), $this->createPrimaryAnswerPayload($options[0]->id))
            ->assertStatus(200);

        $answer = Answer::where('assessment_session_id', $session->id)->firstOrFail();
        $this->assertSame(0, $answer->optionRatings()->count());

        // Act: the student now explicitly rates the option 0
        $response = $this->actingAs($student)
            ->putJson(route('assessment.answers.update', [
                'assessmentSession' => $session->id,
                'question' => $question->id,
            ]), $this->createPrimaryAnswerPayload($options[0]->id, [
                ['option_id' => $options[0]->id, 'rating' => 0],
            ]));

        // Assert (HTTP + DB): absence became a real row carrying 0
        $response->assertStatus(200);
        $this->assertSame(1, $answer->optionRatings()->count(), 'يجب أن يُنشأ صف التقييم بقيمة صفر.');
        $this->assertDatabaseHas('answer_option_ratings', [
            'answer_id' => $answer->id,
            'question_option_id' => $options[0]->id,
            'rating' => 0,
        ]);
    }

    /**
     * @return array<string, array{0: mixed}>
     */
    public static function unapprovedRatingProvider(): array
    {
        return [
            'decimal value' => [1.5],
            'non numeric string' => ['high'],
            'null inside a present rating element' => [null],
        ];
    }

    #[DataProvider('unapprovedRatingProvider')]
    public function test_unapproved_rating_values_are_rejected_and_preserve_the_previous_state(mixed $rating): void
    {
        // Arrange
        $student = $this->createStudentUser();
        $version = $this->createPublishedAssessmentVersion(1);
        $session = $this->createInProgressSession($student, $version);
        $question = $version->questions()->where('position', 1)->first();
        $options = $question->questionOptions;

        // Previous State
        $first = $this->actingAs($student)
            ->putJson(route('assessment.answers.update', [
                'assessmentSession' => $session->id,
                'question' => $question->id,
            ]), $this->createPrimaryAnswerPayload($options[0]->id, [
                ['option_id' => $options[0]->id, 'rating' => 2],
            ]));
        $first->assertStatus(200);

        $answer = Answer::where('assessment_session_id', $session->id)->firstOrFail();
        $previousRatings = $this->captureRatingsSnapshot($answer);
        $previousProcessed = $first->json('data.progress.processed');

        // Act: an out-of-contract rating value
        $response = $this->actingAs($student)
            ->putJson(route('assessment.answers.update', [
                'assessmentSession' => $session->id,
                'question' => $question->id,
            ]), $this->createPrimaryAnswerPayload($options[1]->id, [
                ['option_id' => $options[0]->id, 'rating' => $rating],
            ]));

        // Assert (HTTP)
        $this->assertSame(422, $response->getStatusCode(), 'قيمة التقييم غير المعتمدة يجب أن تُرفض.');
        $response->assertJson([
            'message' => 'بيانات الإجابة غير صالحة.',
            'code' => 'ANSWER_INVALID',
        ]);

        // Assert (DB + Previous State)
        $this->assertSame(1, Answer::where('assessment_session_id', $session->id)->count());
        $this->assertSame($previousRatings, $this->captureRatingsSnapshot($answer), 'التقييمات السابقة يجب ألا تتغير.');

        $resume = $this->actingAs($student)
            ->getJson(route('assessment.show', $session->id));
        $resume->assertStatus(200);
        $this->assertSame($previousProcessed, $resume->json('data.progress.processed'), 'التقدم يجب ألا يتغير بعد الرفض.');
    }
}
