<?php

namespace Tests\Feature\AssessmentJourney;

use App\Models\Answer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\AssessmentJourneyFixtures;
use Tests\TestCase;

/**
 * Q-03 — Phase 2: the save endpoint is an upsert, not an insert — the same
 * question keeps the same answer row, the last successful save wins, and
 * progress never advances on a re-save or an edit.
 *
 * tests/Feature/AssessmentSessionTest.php already covers the basic update
 * (test_saving_same_question_updates_existing_answer), the ratings sync
 * (test_ratings_are_synchronized_with_latest_save), the replay
 * (test_replaying_identical_save_creates_no_duplicate_answer) and the
 * primary → unable_to_judge transition. This file strengthens those with
 * answer-id stability and ordered rating snapshots, and adds the remaining
 * mode transitions plus the "last successful save wins after a rejection"
 * chain.
 */
class AnswerUpsertAndIdempotencyTest extends TestCase
{
    use RefreshDatabase;
    use AssessmentJourneyFixtures;

    public function test_replaying_the_same_request_keeps_the_same_answer_id_and_ratings(): void
    {
        // Arrange
        $student = $this->createStudentUser();
        $version = $this->createPublishedAssessmentVersion(1);
        $session = $this->createInProgressSession($student, $version);
        $question = $version->questions()->where('position', 1)->first();
        $options = $question->questionOptions;

        $payload = $this->createPrimaryAnswerPayload($options[0]->id, [
            ['option_id' => $options[0]->id, 'rating' => 2],
            ['option_id' => $options[1]->id, 'rating' => -1],
        ]);

        // Save 1
        $first = $this->actingAs($student)
            ->putJson(route('assessment.answers.update', [
                'assessmentSession' => $session->id,
                'question' => $question->id,
            ]), $payload);
        $first->assertStatus(200);

        $answer = Answer::where('assessment_session_id', $session->id)->firstOrFail();
        $previousAnswerId = $answer->id;
        $previousRatings = $this->captureRatingsSnapshot($answer);
        $previousProcessed = $first->json('data.progress.processed');

        // Act: the identical request is replayed
        $replay = $this->actingAs($student)
            ->putJson(route('assessment.answers.update', [
                'assessmentSession' => $session->id,
                'question' => $question->id,
            ]), $payload);

        // Assert (HTTP)
        $replay->assertStatus(200);
        $this->assertTrue($replay->json('data.saved'), 'إعادة الطلب يجب أن تنجح.');
        $this->assertSame($previousProcessed, $replay->json('data.progress.processed'), 'التقدم يجب ألا يزيد عند إعادة الطلب.');

        // Assert (DB): one answer, the same row, identical ratings
        $this->assertSame(1, Answer::where('assessment_session_id', $session->id)->count(), 'لا يجب إنشاء إجابة إضافية.');
        $this->assertSame($previousAnswerId, $answer->refresh()->id, 'معرّف الإجابة يجب أن يبقى كما هو.');
        $this->assertSame($previousRatings, $this->captureRatingsSnapshot($answer), 'لا يجب تكرار التقييمات.');
        $this->assertSame(count($previousRatings), $answer->optionRatings()->count());
    }

    public function test_updating_to_a_new_primary_replaces_the_previous_ratings(): void
    {
        // Arrange
        $student = $this->createStudentUser();
        $version = $this->createPublishedAssessmentVersion(1);
        $session = $this->createInProgressSession($student, $version);
        $question = $version->questions()->where('position', 1)->first();
        $options = $question->questionOptions;

        // Save A: primary = option 1 with two ratings
        $this->actingAs($student)
            ->putJson(route('assessment.answers.update', [
                'assessmentSession' => $session->id,
                'question' => $question->id,
            ]), $this->createPrimaryAnswerPayload($options[0]->id, [
                ['option_id' => $options[0]->id, 'rating' => 2],
                ['option_id' => $options[1]->id, 'rating' => 1],
            ]))->assertStatus(200);

        $answer = Answer::where('assessment_session_id', $session->id)->firstOrFail();
        $answerIdBefore = $answer->id;
        $processedBefore = $session->fresh()->answers()->count();

        // Act: Save B — a different primary and a different ratings list
        $response = $this->actingAs($student)
            ->putJson(route('assessment.answers.update', [
                'assessmentSession' => $session->id,
                'question' => $question->id,
            ]), $this->createPrimaryAnswerPayload($options[2]->id, [
                ['option_id' => $options[2]->id, 'rating' => -1],
                ['option_id' => $options[3]->id, 'rating' => 0],
            ]));

        // Assert (HTTP)
        $response->assertStatus(200);
        $this->assertSame($options[2]->id, $response->json('data.answer.primary_option_id'), 'الاختيار الجديد فقط هو المعتمد.');

        // Assert (DB): one effective answer, same row id, only B's ratings
        $this->assertSame(1, Answer::where('assessment_session_id', $session->id)->count(), 'لا يجب إنشاء إجابة إضافية.');
        $this->assertSame($answerIdBefore, $answer->refresh()->id, 'يجب تحديث الصف نفسه لا إنشاء صف جديد.');
        $this->assertSame($options[2]->id, $answer->primary_option_id);
        $this->assertSame(
            [$options[2]->id => -1, $options[3]->id => 0],
            $this->captureRatingsSnapshot($answer),
            'تقييمات B فقط هي المتبقية.'
        );
        $this->assertSame($processedBefore, $session->fresh()->answers()->count(), 'التقدم يجب ألا يزيد عند التعديل.');
    }

    public function test_the_last_successful_save_prevails_after_a_rejected_one(): void
    {
        // Arrange
        $student = $this->createStudentUser();
        $version = $this->createPublishedAssessmentVersion(1);
        $session = $this->createInProgressSession($student, $version);
        $question = $version->questions()->where('position', 1)->first();
        $options = $question->questionOptions;

        // Save A
        $this->actingAs($student)
            ->putJson(route('assessment.answers.update', [
                'assessmentSession' => $session->id,
                'question' => $question->id,
            ]), $this->createPrimaryAnswerPayload($options[0]->id, [
                ['option_id' => $options[0]->id, 'rating' => 2],
            ]))->assertStatus(200);

        // Save B (the latest successful state)
        $second = $this->actingAs($student)
            ->putJson(route('assessment.answers.update', [
                'assessmentSession' => $session->id,
                'question' => $question->id,
            ]), $this->createPrimaryAnswerPayload($options[1]->id, [
                ['option_id' => $options[1]->id, 'rating' => 1],
            ]));
        $second->assertStatus(200);

        $answer = Answer::where('assessment_session_id', $session->id)->firstOrFail();
        $ratingsAfterB = $this->captureRatingsSnapshot($answer);
        $processedAfterB = $second->json('data.progress.processed');

        // Act: Save C is invalid (out-of-range rating) and must be rejected
        $rejected = $this->actingAs($student)
            ->putJson(route('assessment.answers.update', [
                'assessmentSession' => $session->id,
                'question' => $question->id,
            ]), $this->createPrimaryAnswerPayload($options[2]->id, [
                ['option_id' => $options[2]->id, 'rating' => 9],
            ]));

        // Assert (HTTP)
        $rejected->assertStatus(422);

        // Assert (DB): B is still the effective state
        $this->assertSame(1, Answer::where('assessment_session_id', $session->id)->count());
        $this->assertSame($options[1]->id, $answer->refresh()->primary_option_id, 'آخر حفظ ناجح (B) هو المعتمد.');
        $this->assertSame($ratingsAfterB, $this->captureRatingsSnapshot($answer), 'تقييمات B يجب ألا تتغير.');

        // Assert (read endpoint): the resume payload reflects B, and progress is untouched
        $resume = $this->actingAs($student)
            ->getJson(route('assessment.show', $session->id));
        $resume->assertStatus(200);
        $this->assertSame($processedAfterB, $resume->json('data.progress.processed'), 'التقدم يجب ألا يتغير بعد الرفض.');
        $savedAnswer = collect($resume->json('data.saved_answers'))->firstWhere('question_id', $question->id);
        $this->assertSame($options[1]->id, $savedAnswer['primary_option_id']);
        $this->assertSame($ratingsAfterB, collect($savedAnswer['ratings'])->pluck('rating', 'option_id')->all());
    }

    public function test_switching_from_a_primary_pick_to_none_selected_clears_the_primary_option(): void
    {
        // Arrange
        $student = $this->createStudentUser();
        $version = $this->createPublishedAssessmentVersion(1);
        $session = $this->createInProgressSession($student, $version);
        $question = $version->questions()->where('position', 1)->first();
        $options = $question->questionOptions;

        $this->actingAs($student)
            ->putJson(route('assessment.answers.update', [
                'assessmentSession' => $session->id,
                'question' => $question->id,
            ]), $this->createPrimaryAnswerPayload($options[0]->id, [
                ['option_id' => $options[0]->id, 'rating' => 2],
            ]))->assertStatus(200);

        $answer = Answer::where('assessment_session_id', $session->id)->firstOrFail();
        $processedBefore = $session->fresh()->answers()->count();

        // Act: switch to none_selected
        $response = $this->actingAs($student)
            ->putJson(route('assessment.answers.update', [
                'assessmentSession' => $session->id,
                'question' => $question->id,
            ]), [
                'primary_option_id' => null,
                'none_selected' => true,
                'unable_to_judge' => false,
                'ratings' => [],
            ]);

        // Assert (HTTP)
        $response->assertStatus(200);
        $this->assertNull($response->json('data.answer.primary_option_id'));
        $this->assertTrue($response->json('data.answer.none_selected'));

        // Assert (DB)
        $this->assertSame(1, Answer::where('assessment_session_id', $session->id)->count(), 'لا يجب إنشاء إجابة إضافية.');
        $this->assertNull($answer->refresh()->primary_option_id, 'الاختيار الأساسي يجب أن يُزال.');
        $this->assertSame('none', $answer->response_type);
        $this->assertSame(0, $answer->optionRatings()->count(), 'التقييمات القديمة يجب أن تُحذف.');
        $this->assertSame($processedBefore, $session->fresh()->answers()->count(), 'التقدم يجب ألا يزيد عند تغيير النمط.');
    }

    public function test_switching_from_none_selected_to_a_primary_pick_stores_the_primary_option(): void
    {
        // Arrange
        $student = $this->createStudentUser();
        $version = $this->createPublishedAssessmentVersion(1);
        $session = $this->createInProgressSession($student, $version);
        $question = $version->questions()->where('position', 1)->first();
        $options = $question->questionOptions;

        $noneSelectedPayload = [
            'primary_option_id' => null,
            'none_selected' => true,
            'unable_to_judge' => false,
            'ratings' => [],
        ];

        $this->actingAs($student)
            ->putJson(route('assessment.answers.update', [
                'assessmentSession' => $session->id,
                'question' => $question->id,
            ]), $noneSelectedPayload)->assertStatus(200);

        $answer = Answer::where('assessment_session_id', $session->id)->firstOrFail();
        $processedBefore = $session->fresh()->answers()->count();

        // Act: switch back to a primary pick
        $response = $this->actingAs($student)
            ->putJson(route('assessment.answers.update', [
                'assessmentSession' => $session->id,
                'question' => $question->id,
            ]), $this->createPrimaryAnswerPayload($options[1]->id, [
                ['option_id' => $options[1]->id, 'rating' => 1],
            ]));

        // Assert (HTTP)
        $response->assertStatus(200);
        $this->assertSame($options[1]->id, $response->json('data.answer.primary_option_id'));

        // Assert (DB)
        $this->assertSame(1, Answer::where('assessment_session_id', $session->id)->count(), 'لا يجب إنشاء إجابة إضافية.');
        $this->assertSame($options[1]->id, $answer->refresh()->primary_option_id);
        $this->assertSame('option', $answer->response_type);
        $this->assertSame([$options[1]->id => 1], $this->captureRatingsSnapshot($answer));
        $this->assertSame($processedBefore, $session->fresh()->answers()->count(), 'التقدم يجب ألا يزيد عند تغيير النمط.');
    }

    public function test_switching_from_unable_to_judge_to_a_primary_pick_stores_the_primary_option(): void
    {
        // Arrange
        $student = $this->createStudentUser();
        $version = $this->createPublishedAssessmentVersion(1);
        $session = $this->createInProgressSession($student, $version);
        $question = $version->questions()->where('position', 1)->first();
        $options = $question->questionOptions;

        $unableToJudgePayload = [
            'primary_option_id' => null,
            'none_selected' => false,
            'unable_to_judge' => true,
            'ratings' => [],
        ];

        $this->actingAs($student)
            ->putJson(route('assessment.answers.update', [
                'assessmentSession' => $session->id,
                'question' => $question->id,
            ]), $unableToJudgePayload)->assertStatus(200);

        $answer = Answer::where('assessment_session_id', $session->id)->firstOrFail();
        $this->assertSame('cannot_judge', $answer->response_type);
        $processedBefore = $session->fresh()->answers()->count();

        // Act: switch from cannot_judge to a primary pick
        $response = $this->actingAs($student)
            ->putJson(route('assessment.answers.update', [
                'assessmentSession' => $session->id,
                'question' => $question->id,
            ]), $this->createPrimaryAnswerPayload($options[0]->id, [
                ['option_id' => $options[0]->id, 'rating' => 2],
            ]));

        // Assert (HTTP)
        $response->assertStatus(200);
        $this->assertSame($options[0]->id, $response->json('data.answer.primary_option_id'));

        // Assert (DB)
        $this->assertSame(1, Answer::where('assessment_session_id', $session->id)->count(), 'لا يجب إنشاء إجابة إضافية.');
        $this->assertSame($options[0]->id, $answer->refresh()->primary_option_id);
        $this->assertSame('option', $answer->response_type);
        $this->assertSame([$options[0]->id => 2], $this->captureRatingsSnapshot($answer));
        $this->assertSame($processedBefore, $session->fresh()->answers()->count(), 'التقدم يجب ألا يزيد عند تغيير النمط.');
    }
}
