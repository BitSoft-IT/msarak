<?php

namespace Tests\Feature\AssessmentJourney;

use App\Models\Answer;
use App\Models\Question;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\Support\AssessmentJourneyFixtures;
use Tests\TestCase;

/**
 * Q-03 — Phase 3: resume correctness and progress accounting.
 *
 * The read path (GET show) is the contract surface the browser rebuilds the
 * journey from, so every test here re-reads the session through the real
 * endpoint instead of trusting the write response alone.
 *
 * Coverage that already exists elsewhere and is therefore NOT repeated here:
 *   - tests/Feature/AssessmentSessionTest.php::test_resume_returns_full_session_state_and_restores_answers
 *       (saved_answers values for a two-answer session)
 *   - SessionReuseAndVersionTest (start with a resumable session reuses it)
 *   - AnswerUpsertAndIdempotencyTest (answer-id stability on edit)
 */
class SessionResumeAndProgressTest extends TestCase
{
    use RefreshDatabase;
    use AssessmentJourneyFixtures;

    public function test_a_session_without_answers_reports_zero_progress_at_the_first_question(): void
    {
        // Arrange
        $student = $this->createStudentUser();
        $version = $this->createPublishedAssessmentVersion(1);
        $session = $this->createInProgressSession($student, $version);

        // Act
        $response = $this->actingAs($student)->getJson(route('assessment.show', $session->id));

        // Assert (HTTP)
        $response->assertStatus(200);
        $this->assertSame($session->id, $response->json('data.session.session_id'));
        $this->assertSame('in_progress', $response->json('data.session.status'));
        $this->assertSame(0, $response->json('data.progress.processed'), 'لا توجد إجابات بعد.');
        $this->assertSame(18, $response->json('data.progress.total'));
        $this->assertSame(1, $response->json('data.progress.current_position'), 'البداية يجب أن تكون عند السؤال الأول.');
        $this->assertSame(18, count($response->json('data.questions')), 'جميع أسئلة الإصدار يجب أن تُعرض.');
        $this->assertEmpty($response->json('data.saved_answers'));

        // Assert (DB): the read path never writes
        $this->assertSame(0, Answer::where('assessment_session_id', $session->id)->count());
    }

    public function test_one_answer_advances_processed_and_moves_current_position_to_the_next_question(): void
    {
        // Arrange
        $student = $this->createStudentUser();
        $version = $this->createPublishedAssessmentVersion(1);
        $session = $this->createInProgressSession($student, $version);
        $first = $this->questionAtPosition($version, 1);
        $second = $this->questionAtPosition($version, 2);

        // Act: one successful save, then a fresh read
        $save = $this->savePrimaryAnswer($student, $session, $first, $first->questionOptions[0]->id);
        $save->assertStatus(200);
        $this->assertSame(1, $save->json('data.progress.processed'));

        $response = $this->actingAs($student)->getJson(route('assessment.show', $session->id));

        // Assert (HTTP)
        $response->assertStatus(200);
        $this->assertSame(1, $response->json('data.progress.processed'));
        $this->assertSame(2, $response->json('data.progress.current_position'), 'المؤشر يجب أن ينتقل للسؤال التالي غير المُجاب.');

        $savedAnswer = collect($response->json('data.saved_answers'))->firstWhere('question_id', $first->id);
        $this->assertNotNull($savedAnswer, 'الإجابة المحفوظة يجب أن تعود في الحمولة.');
        $this->assertSame($first->questionOptions[0]->id, $savedAnswer['primary_option_id']);
        $this->assertFalse($savedAnswer['none_selected']);
        $this->assertFalse($savedAnswer['unable_to_judge']);

        // Assert (DB)
        $this->assertSame(1, Answer::where('assessment_session_id', $session->id)->count());
        $this->assertSame(2, $second->position);
    }

    public function test_current_position_points_at_the_first_unanswered_question_even_when_later_questions_are_answered(): void
    {
        // Arrange: answer positions 1, 2 and 4, deliberately leaving 3 empty
        $student = $this->createStudentUser();
        $version = $this->createPublishedAssessmentVersion(1);
        $session = $this->createInProgressSession($student, $version);

        foreach ([1, 2, 4] as $position) {
            $question = $this->questionAtPosition($version, $position);
            $this->savePrimaryAnswer($student, $session, $question, $question->questionOptions[0]->id)
                ->assertStatus(200);
        }

        // Act
        $response = $this->actingAs($student)->getJson(route('assessment.show', $session->id));

        // Assert (HTTP): processed counts answers, current_position points at the hole
        $response->assertStatus(200);
        $this->assertSame(3, $response->json('data.progress.processed'), 'عدد الإجابات هو 3 رغم الفجوة.');
        $this->assertSame(3, $response->json('data.progress.current_position'), 'المؤشر يجب أن يشير لأول سؤال غير مُجاب (3) لا للسؤال الذي بعده (5).');
        $this->assertSame(18, $response->json('data.progress.total'));

        $answeredQuestionIds = collect($response->json('data.saved_answers'))->pluck('question_id')->all();
        $this->assertNotContains($this->questionAtPosition($version, 3)->id, $answeredQuestionIds);
        $this->assertSame(3, count($answeredQuestionIds));

        // Assert (DB)
        $this->assertSame(3, Answer::where('assessment_session_id', $session->id)->count());
    }

    public function test_editing_a_saved_answer_does_not_increase_processed_progress(): void
    {
        // Arrange
        $student = $this->createStudentUser();
        $version = $this->createPublishedAssessmentVersion(1);
        $session = $this->createInProgressSession($student, $version);
        $question = $this->questionAtPosition($version, 1);
        $options = $question->questionOptions;

        // Save A
        $this->savePrimaryAnswer($student, $session, $question, $options[0]->id, [
            ['option_id' => $options[0]->id, 'rating' => 2],
        ])->assertStatus(200);

        $answer = Answer::where('assessment_session_id', $session->id)->firstOrFail();
        $previousAnswerId = $answer->id;

        // Act: edit the same question to a different primary option
        $edit = $this->savePrimaryAnswer($student, $session, $question, $options[2]->id, [
            ['option_id' => $options[2]->id, 'rating' => -1],
        ]);

        // Assert (HTTP): progress is unchanged by the edit
        $edit->assertStatus(200);
        $this->assertTrue($edit->json('data.saved'));
        $this->assertSame(1, $edit->json('data.progress.processed'), 'التعديل لا يجب أن يزيد التقدم.');

        // Assert (DB): one row, same id, new values
        $this->assertSame(1, Answer::where('assessment_session_id', $session->id)->count(), 'لا يجب إنشاء إجابة إضافية.');
        $this->assertSame($previousAnswerId, $answer->refresh()->id);
        $this->assertSame($options[2]->id, $answer->primary_option_id);
        $this->assertSame([$options[2]->id => -1], $this->captureRatingsSnapshot($answer));

        // Assert (read endpoint): the cursor has not moved either
        $response = $this->actingAs($student)->getJson(route('assessment.show', $session->id));
        $response->assertStatus(200);
        $this->assertSame(1, $response->json('data.progress.processed'));
        $this->assertSame(2, $response->json('data.progress.current_position'));
    }

    public function test_answering_every_question_sets_current_position_to_total_without_completing(): void
    {
        // Arrange
        $student = $this->createStudentUser();
        $version = $this->createPublishedAssessmentVersion(1);
        $session = $this->createInProgressSession($student, $version);

        // Act: answer the whole bank, position by position
        foreach ($version->questions()->orderBy('position')->get() as $question) {
            $this->savePrimaryAnswer($student, $session, $question, $question->questionOptions[0]->id)
                ->assertStatus(200);
        }

        // Assert (HTTP): the cursor collapses to total, status stays open
        $response = $this->actingAs($student)->getJson(route('assessment.show', $session->id));
        $response->assertStatus(200);
        $this->assertSame(18, $response->json('data.progress.processed'));
        $this->assertSame(18, $response->json('data.progress.total'));
        $this->assertSame(18, $response->json('data.progress.current_position'), 'بعد إجابة كل الأسئلة يجب أن يساوي المؤشر المجموع.');
        $this->assertSame('in_progress', $response->json('data.session.status'), 'القراءة وحدها لا تُكمل الجلسة.');
        $this->assertSame(18, count($response->json('data.saved_answers')));

        // Assert (DB): fully answered, still in progress, no result materialised
        $this->assertSame(18, Answer::where('assessment_session_id', $session->id)->count());
        $this->assertSame('in_progress', $session->fresh()->status);
        $this->assertSame(0, \DB::table('results')->where('assessment_session_id', $session->id)->count());
    }

    public function test_a_resume_cycle_preserves_prior_answers_and_advances_progress(): void
    {
        // Arrange
        $student = $this->createStudentUser();
        $version = $this->createPublishedAssessmentVersion(1);
        $session = $this->createInProgressSession($student, $version);
        $first = $this->questionAtPosition($version, 1);
        $second = $this->questionAtPosition($version, 2);

        // Cycle 1: answer Q1, then reopen
        $this->savePrimaryAnswer($student, $session, $first, $first->questionOptions[0]->id, [
            ['option_id' => $first->questionOptions[0]->id, 'rating' => 2],
        ])->assertStatus(200);

        $firstRead = $this->actingAs($student)->getJson(route('assessment.show', $session->id));
        $firstRead->assertStatus(200);
        $this->assertSame(1, $firstRead->json('data.progress.processed'));
        $this->assertSame(2, $firstRead->json('data.progress.current_position'));
        $firstAnswerRow = collect($firstRead->json('data.saved_answers'))->firstWhere('question_id', $first->id);
        $this->assertSame($first->questionOptions[0]->id, $firstAnswerRow['primary_option_id']);

        // Act: continue the same session with Q2 and reopen again
        $this->savePrimaryAnswer($student, $session, $second, $second->questionOptions[1]->id, [
            ['option_id' => $second->questionOptions[1]->id, 'rating' => 1],
        ])->assertStatus(200);

        $response = $this->actingAs($student)->getJson(route('assessment.show', $session->id));

        // Assert (HTTP): both answers survive, progress advanced
        $response->assertStatus(200);
        $this->assertSame(2, $response->json('data.progress.processed'));
        $this->assertSame(3, $response->json('data.progress.current_position'));

        $savedAnswers = collect($response->json('data.saved_answers'));
        $this->assertSame(2, $savedAnswers->count());
        $reopenedFirst = $savedAnswers->firstWhere('question_id', $first->id);
        $this->assertSame($first->questionOptions[0]->id, $reopenedFirst['primary_option_id'], 'الإجابة الأولى يجب أن تبقى سليمة بعد الحفظ الثاني.');
        $this->assertSame(
            [$first->questionOptions[0]->id => 2],
            collect($reopenedFirst['ratings'])->pluck('rating', 'option_id')->all()
        );
        $reopenedSecond = $savedAnswers->firstWhere('question_id', $second->id);
        $this->assertSame($second->questionOptions[1]->id, $reopenedSecond['primary_option_id']);

        // Assert (DB)
        $this->assertSame(2, Answer::where('assessment_session_id', $session->id)->count());
    }

    public function test_an_owner_can_read_their_completed_session_without_result_or_scoring_payload(): void
    {
        // Arrange: a completed session exactly as the fixture builds it
        $student = $this->createStudentUser();
        $version = $this->createPublishedAssessmentVersion(1);
        $session = $this->createCompletedSession($student, $version);

        // Act: the owner reads their own finished session
        $response = $this->actingAs($student)->getJson(route('assessment.show', $session->id));

        // Assert (HTTP)
        $response->assertStatus(200);
        $this->assertSame('completed', $response->json('data.session.status'));
        $this->assertSame($version->id, $response->json('data.session.assessment_version_id'));

        // The read path must stay a journey read: no result or scoring object
        $data = $response->json('data');
        $this->assertArrayNotHasKey('result', $data);
        $this->assertArrayNotHasKey('scores', $data);
        $this->assertArrayNotHasKey('recommendations', $data);
        foreach (['session', 'progress', 'questions', 'saved_answers'] as $key) {
            $this->assertArrayHasKey($key, $data, "المفتاح المعتمد {$key} يجب أن يبقى موجودًا.");
        }

        // Assert (DB): reading never materialises a result
        $this->assertSame(0, \DB::table('results')->where('assessment_session_id', $session->id)->count());
        $this->assertSame(0, \DB::table('result_scores')->count());
        $this->assertSame(0, \DB::table('result_recommendations')->count());
    }

    private function questionAtPosition($version, int $position): Question
    {
        return $version->questions()->where('position', $position)->firstOrFail();
    }

    private function savePrimaryAnswer($student, $session, Question $question, int $primaryOptionId, array $ratings = []): TestResponse
    {
        return $this->actingAs($student)
            ->putJson(route('assessment.answers.update', [
                'assessmentSession' => $session->id,
                'question' => $question->id,
            ]), $this->createPrimaryAnswerPayload($primaryOptionId, $ratings));
    }
}
