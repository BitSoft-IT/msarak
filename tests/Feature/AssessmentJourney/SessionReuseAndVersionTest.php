<?php

namespace Tests\Feature\AssessmentJourney;

use App\Models\AssessmentSession;
use App\Models\Result;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\AssessmentJourneyFixtures;
use Tests\TestCase;

/**
 * Q-03 — Phase 1: session reuse, version pinning and cross-user isolation on
 * the start endpoint.
 *
 * The reuse of an existing incomplete session is already covered by
 * tests/Feature/AssessmentSessionTest.php, so this file targets the states that
 * are not: starting after a completed session, starting after a newer version
 * has been published, cross-user isolation, and the absence of result rows.
 */
class SessionReuseAndVersionTest extends TestCase
{
    use RefreshDatabase;
    use AssessmentJourneyFixtures;

    // ===============================================================
    // 1. A completed session does not block a fresh start
    // ===============================================================

    public function test_start_after_completed_session_creates_a_new_in_progress_session(): void
    {
        // Arrange
        $student = $this->createStudentUser();
        $version = $this->createPublishedAssessmentVersion(1);
        $completed = $this->createCompletedSession($student, $version);

        // Act: the previous session is completed, so no incomplete session exists to resume
        $response = $this->actingAs($student)
            ->postJson(route('assessment.sessions.store'));

        // Assert (HTTP): a brand-new session is created, not resumed
        $this->assertSame(201, $response->getStatusCode(), 'انتهاء الجلسة السابقة يجب أن يؤدي إلى إنشاء جلسة جديدة 201.');
        $this->assertFalse($response->json('data.resumed'), 'الجلسة الجديدة يجب ألا تُعتبر استئنافًا.');
        $this->assertNotSame($completed->id, $response->json('data.session_id'), 'يجب ألا تُعاد الجلسة المكتملة.');

        // Assert (DB): the student now owns the completed session plus a new in-progress one
        $this->assertSame(2, AssessmentSession::where('user_id', $student->id)->count());
        $this->assertDatabaseHas('assessment_sessions', [
            'id' => $response->json('data.session_id'),
            'user_id' => $student->id,
            'assessment_version_id' => $version->id,
            'status' => 'in_progress',
        ]);
        // الجلسة المكتملة تبقى كما هي دون تغيير
        $this->assertSame('completed', AssessmentSession::find($completed->id)->status);
    }

    // ===============================================================
    // 2. A newer published version starts a new session on it
    // ===============================================================

    public function test_start_after_newer_version_published_creates_session_on_the_new_version(): void
    {
        // Arrange: the student has an in-progress session on version 1
        $student = $this->createStudentUser();
        $v1 = $this->createPublishedAssessmentVersion(1);
        $v1Session = $this->createInProgressSession($student, $v1);

        // Version 1 is retired and version 2 becomes the published version
        $v1->update(['status' => 'retired']);
        $v2 = $this->createPublishedAssessmentVersion(2);

        // Act
        $response = $this->actingAs($student)
            ->postJson(route('assessment.sessions.store'));

        // Assert (HTTP): the start resolves the currently published version (version 2)
        $this->assertSame(201, $response->getStatusCode(), 'البدء بعد نشر إصدار أحدث يجب أن ينشئ جلسة جديدة 201.');
        $this->assertFalse($response->json('data.resumed'), 'لا يوجد استئناف لأن الجلسة القديمة مرتبطة بإصدار سابق.');
        $this->assertSame($v2->id, $response->json('data.assessment_version_id'), 'الجلسة الجديدة يجب أن ترتبط بالإصدار المنشور الحالي.');

        // Assert (DB): the new session is on version 2, and the version-1 session is left untouched
        $this->assertSame(2, AssessmentSession::where('user_id', $student->id)->count());
        $this->assertDatabaseHas('assessment_sessions', [
            'id' => $response->json('data.session_id'),
            'user_id' => $student->id,
            'assessment_version_id' => $v2->id,
            'status' => 'in_progress',
        ]);
        // جلسة الإصدار الأول تبقى قائمة بحالتها الأصلية
        $this->assertSame($v1->id, AssessmentSession::find($v1Session->id)->assessment_version_id);
        $this->assertSame('in_progress', AssessmentSession::find($v1Session->id)->status);
    }

    // ===============================================================
    // 3. Starting never returns another student's session
    // ===============================================================

    public function test_start_never_returns_another_students_in_progress_session(): void
    {
        // Arrange: student A already owns an in-progress session
        $version = $this->createPublishedAssessmentVersion(1);
        $studentA = $this->createStudentUser();
        $studentB = $this->createStudentUser();
        $sessionA = $this->createInProgressSession($studentA, $version);

        // Act: student B starts their own assessment
        $response = $this->actingAs($studentB)
            ->postJson(route('assessment.sessions.store'));

        // Assert (HTTP): B receives a different, brand-new session
        $this->assertSame(201, $response->getStatusCode(), 'بدء الطالب الثاني يجب أن ينشئ جلسة خاصة به 201.');
        $this->assertFalse($response->json('data.resumed'));
        $this->assertNotSame($sessionA->id, $response->json('data.session_id'), 'يجب ألا تُعاد جلسة طالب آخر.');

        // Assert (DB): each student owns exactly one session, and A's session is untouched
        $this->assertSame(1, AssessmentSession::where('user_id', $studentA->id)->count());
        $this->assertSame(1, AssessmentSession::where('user_id', $studentB->id)->count());
        $this->assertSame($studentA->id, AssessmentSession::find($sessionA->id)->user_id);
        $this->assertSame($version->id, AssessmentSession::find($sessionA->id)->assessment_version_id);
        $this->assertSame('in_progress', AssessmentSession::find($sessionA->id)->status);
    }

    // ===============================================================
    // 4. Starting never materialises a result
    // ===============================================================

    public function test_start_never_creates_result_rows(): void
    {
        // Arrange
        $student = $this->createStudentUser();
        $this->createPublishedAssessmentVersion(1);

        // Act: two sequential start requests (create, then resume)
        $first = $this->actingAs($student)->postJson(route('assessment.sessions.store'));
        $this->assertSame(201, $first->getStatusCode(), 'الطلب الأول ينشئ الجلسة.');
        $second = $this->actingAs($student)->postJson(route('assessment.sessions.store'));
        $this->assertSame(200, $second->getStatusCode(), 'الطلب الثاني يستأنف نفس الجلسة.');

        // Assert (DB): no result side effects at any point of the start flow
        $this->assertSame(0, Result::count(), 'البدء لا يجب أن ينشئ أي نتيجة.');
        $this->assertSame(0, DB::table('result_scores')->count(), 'البدء لا يجب أن ينشئ درجات.');
        $this->assertSame(0, DB::table('result_recommendations')->count(), 'البدء لا يجب أن ينشئ توصيات.');
    }
}
