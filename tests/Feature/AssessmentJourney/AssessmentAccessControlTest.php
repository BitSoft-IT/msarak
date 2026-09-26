<?php

namespace Tests\Feature\AssessmentJourney;

use App\Models\Answer;
use App\Models\AssessmentSession;
use App\Models\Question;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\AssessmentJourneyFixtures;
use Tests\TestCase;

/**
 * Q-03 — Phase 3: server-side ownership and role enforcement on the journey.
 *
 * The matrix this file pins, derived from routes/web.php (every assessment
 * route sits under ['auth', 'role:student']) and AssessmentSessionPolicy
 * (ownership-only view/update):
 *   - guest (JSON)            -> 401 on both the read and the write endpoint
 *   - admin                   -> 403 FORBIDDEN (role middleware, before policy)
 *   - another student         -> 404 RESOURCE_NOT_FOUND, owner state untouched
 *   - missing session         -> 404 RESOURCE_NOT_FOUND (same shape)
 *   - owner of completed      -> 409 SESSION_COMPLETED, no row changes
 *
 * The single cross-user GET case is already covered by
 * tests/Feature/AssessmentSessionTest.php::test_user_cannot_resume_another_users_session
 * and AssessmentUiTest::test_student_cannot_view_another_students_assessment_show_page,
 * so it is not repeated here.
 */
class AssessmentAccessControlTest extends TestCase
{
    use RefreshDatabase;
    use AssessmentJourneyFixtures;

    private const MISSING_SESSION_ID = 999999;

    #[DataProvider('readWriteEndpointProvider')]
    public function test_a_guest_is_rejected_by_both_the_read_and_the_write_endpoints(array $mode): void
    {
        // Arrange
        $student = $this->createStudentUser();
        $version = $this->createPublishedAssessmentVersion(1);
        $session = $this->createInProgressSession($student, $version);
        $question = $version->questions()->where('position', 1)->first();

        // Previous State
        $this->assertSame(1, AssessmentSession::count());

        // Act: no authentication at all, JSON expected
        $response = $this->journeyRequest(null, $session, $question, $mode['write']);

        // Assert (HTTP)
        $response->assertStatus(401);
        $this->assertSame('Unauthenticated.', $response->json('message'));

        // Assert (DB): nothing was created or leaked
        $this->assertSame(1, AssessmentSession::count());
        $this->assertSame(0, Answer::count());
    }

    #[DataProvider('readWriteEndpointProvider')]
    public function test_an_admin_is_forbidden_from_the_student_read_and_write_endpoints(array $mode): void
    {
        // Arrange
        $student = $this->createStudentUser();
        $admin = $this->createAdminUser();
        $version = $this->createPublishedAssessmentVersion(1);
        $session = $this->createInProgressSession($student, $version);
        $question = $version->questions()->where('position', 1)->first();
        $options = $question->questionOptions;

        // Act: an authenticated admin hits the student-only endpoints
        $response = $this->journeyRequest(
            $admin,
            $session,
            $question,
            $mode['write'],
            $this->createPrimaryAnswerPayload($options[0]->id)
        );

        // Assert (HTTP): rejected by the role middleware, not the policy
        $response->assertStatus(403);
        $this->assertSame('FORBIDDEN', $response->json('code'));
        $this->assertSame('لا تملك الصلاحية للقيام بهذه العملية.', $response->json('message'));

        // Assert (DB): the student's session was not modified
        $this->assertSame(0, Answer::where('assessment_session_id', $session->id)->count());
    }

    public function test_another_student_cannot_write_into_a_session_and_the_owner_state_is_untouched(): void
    {
        // Arrange
        $owner = $this->createStudentUser();
        $intruder = $this->createStudentUser();
        $version = $this->createPublishedAssessmentVersion(1);
        $session = $this->createInProgressSession($owner, $version);
        $question = $version->questions()->where('position', 1)->first();
        $options = $question->questionOptions;

        // The owner saves a real answer through the endpoint
        $this->journeyRequest(
            $owner,
            $session,
            $question,
            true,
            $this->createPrimaryAnswerPayload($options[0]->id, [
                ['option_id' => $options[0]->id, 'rating' => 2],
                ['option_id' => $options[1]->id, 'rating' => -1],
            ])
        )->assertStatus(200);

        // Previous State: the owner's effective progress and ratings
        $answer = Answer::where('assessment_session_id', $session->id)->firstOrFail();
        $previousRatings = $this->captureRatingsSnapshot($answer);
        $previousAnswersCount = Answer::where('assessment_session_id', $session->id)->count();
        $previousProcessed = $this->actingAs($owner)
            ->getJson(route('assessment.show', $session->id))
            ->json('data.progress.processed');

        // Act: another student tries to overwrite the owner's answer
        $response = $this->journeyRequest(
            $intruder,
            $session,
            $question,
            true,
            $this->createPrimaryAnswerPayload($options[2]->id, [
                ['option_id' => $options[2]->id, 'rating' => 1],
            ])
        );

        // Assert (HTTP): existence is not disclosed
        $response->assertStatus(404);
        $this->assertSame('RESOURCE_NOT_FOUND', $response->json('code'));

        // Assert (DB): the owner's answer, ratings and progress are intact
        $this->assertSame($previousAnswersCount, Answer::where('assessment_session_id', $session->id)->count());
        $this->assertSame($options[0]->id, $answer->refresh()->primary_option_id, 'الاختيار الأساسي للمالك لم يتغير.');
        $this->assertSame($previousRatings, $this->captureRatingsSnapshot($answer), 'تقييمات المالك لم تتغير.');

        // Assert (read endpoint): the owner still sees their own progress
        $this->assertSame(
            $previousProcessed,
            $this->actingAs($owner)->getJson(route('assessment.show', $session->id))->json('data.progress.processed')
        );
    }

    #[DataProvider('readWriteEndpointProvider')]
    public function test_a_missing_session_returns_the_same_not_found_shape_for_both_endpoints(array $mode): void
    {
        // Arrange: a valid question, but a session id that does not exist
        $student = $this->createStudentUser();
        $version = $this->createPublishedAssessmentVersion(1);
        $session = $this->createInProgressSession($student, $version);
        $question = $version->questions()->where('position', 1)->first();
        $options = $question->questionOptions;

        $missingSession = new AssessmentSession;
        $missingSession->id = self::MISSING_SESSION_ID;

        // Act
        $response = $this->journeyRequest(
            $student,
            $missingSession,
            $question,
            $mode['write'],
            $this->createPrimaryAnswerPayload($options[0]->id)
        );

        // Assert (HTTP): the unified not-found envelope on both verbs
        $response->assertStatus(404);
        $this->assertSame('RESOURCE_NOT_FOUND', $response->json('code'));
        $this->assertSame('المورد غير موجود.', $response->json('message'));

        // Assert (DB): the real session was untouched by the probe
        $this->assertSame(1, AssessmentSession::count());
        $this->assertSame(0, Answer::count());
    }

    public function test_the_owner_of_a_completed_session_cannot_edit_any_answer(): void
    {
        // Arrange: a completed session straight from the fixture, no answers seeded
        $student = $this->createStudentUser();
        $version = $this->createPublishedAssessmentVersion(1);
        $session = $this->createCompletedSession($student, $version);
        $question = $version->questions()->where('position', 1)->first();
        $options = $question->questionOptions;

        // Previous State
        $previousAnswersCount = Answer::where('assessment_session_id', $session->id)->count();
        $previousStatus = $session->fresh()->status;
        // Compared as strings: fresh() re-hydrates completed_at into a new Carbon
        // instance, so a strict object comparison would always fail even on an
        // unchanged row.
        $previousCompletedAt = $session->fresh()->completed_at->format('Y-m-d H:i:s');

        // Act: even the owner cannot write into a finished session
        $response = $this->journeyRequest(
            $student,
            $session,
            $question,
            true,
            $this->createPrimaryAnswerPayload($options[0]->id, [
                ['option_id' => $options[0]->id, 'rating' => 2],
            ])
        );

        // Assert (HTTP)
        $response->assertStatus(409);
        $this->assertSame('SESSION_COMPLETED', $response->json('code'));
        $this->assertSame('لا يمكن تعديل إجابات جلسة مكتملة.', $response->json('message'));

        // Assert (DB): no answer written, session row unchanged
        $this->assertSame($previousAnswersCount, Answer::where('assessment_session_id', $session->id)->count(), 'لا يجب حفظ أي إجابة.');
        $this->assertSame(0, Answer::where('assessment_session_id', $session->id)->count());
        $this->assertSame($previousStatus, $session->fresh()->status);
        $this->assertSame($previousCompletedAt, $session->fresh()->completed_at->format('Y-m-d H:i:s'));
    }

    /**
     * Both journey verbs, so the permission matrix is pinned on read and write.
     *
     * @return array<string, array{0: array{write: bool}}>
     */
    public static function readWriteEndpointProvider(): array
    {
        return [
            'قراءة الجلسة (GET show)' => [['write' => false]],
            'حفظ الإجابة (PUT answers)' => [['write' => true]],
        ];
    }

    private function journeyRequest($as, AssessmentSession $session, Question $question, bool $write, array $payload = []): TestResponse
    {
        $builder = $as === null ? $this : $this->actingAs($as);

        if ($write) {
            return $builder->putJson(route('assessment.answers.update', [
                'assessmentSession' => $session->id,
                'question' => $question->id,
            ]), $payload);
        }

        return $builder->getJson(route('assessment.show', $session->id));
    }
}
