<?php

namespace Tests\Feature\AssessmentJourney;

use App\Models\AssessmentSession;
use App\Models\AssessmentVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\AssessmentJourneyFixtures;
use Tests\TestCase;

/**
 * Q-03 — Phase 1: session start availability and the shape of the response.
 *
 * Covers the H-03 "عقد بدء التقييم" concerns that are not already asserted by
 * tests/Feature/AssessmentSessionTest.php: the exact contract envelope, body
 * injection, the remaining unavailable-version states, and the role guard.
 */
class SessionStartAvailabilityTest extends TestCase
{
    use RefreshDatabase;
    use AssessmentJourneyFixtures;

    // ===============================================================
    // 1. Exact contract shape of the start response
    // ===============================================================

    public function test_start_response_contains_exactly_the_contract_fields(): void
    {
        // Arrange
        $student = $this->createStudentUser();
        $version = $this->createPublishedAssessmentVersion(1);

        // Act
        $response = $this->actingAs($student)
            ->postJson(route('assessment.sessions.store'));

        // Assert (HTTP): 201 with exactly the approved envelope, no extra fields
        $this->assertSame(201, $response->getStatusCode(), 'بدء الجلسة الجديدة يجب أن يعيد 201 وفق عقد H-03.');
        $this->assertEqualsCanonicalizing(
            ['data', 'message'],
            array_keys($response->json()),
            'مستوى الاستجابة يجب أن يحتوي data و message فقط، دون أي حقل زائد.'
        );
        $this->assertEqualsCanonicalizing(
            ['session_id', 'status', 'assessment_version_id', 'resumed', 'redirect_url'],
            array_keys($response->json('data')),
            'data يجب أن يحتوي الحقول الخمسة المنصوص عليها في عقد البدء فقط.'
        );
        $this->assertSame('بدأ التقييم.', $response->json('message'), 'الرسالة يجب أن تطابق نص العقد.');
        $this->assertSame("/assessment/sessions/{$response->json('data.session_id')}", $response->json('data.redirect_url'));

        // Assert (DB): one in-progress session owned by the authenticated student
        $this->assertDatabaseHas('assessment_sessions', [
            'id' => $response->json('data.session_id'),
            'user_id' => $student->id,
            'assessment_version_id' => $version->id,
            'status' => 'in_progress',
        ]);
    }

    // ===============================================================
    // 2. The request body must never drive the outcome
    // ===============================================================

    public function test_start_ignores_user_id_and_assessment_version_id_in_the_request_body(): void
    {
        // Arrange: a published version, an unrelated draft, and a second student
        $student = $this->createStudentUser();
        $published = $this->createPublishedAssessmentVersion(1);
        $draft = $this->createDraftVersion(2);
        $otherStudent = $this->createStudentUser();

        // Act: a forged body tries to impersonate another student and pin a draft
        $response = $this->actingAs($student)
            ->postJson(route('assessment.sessions.store'), [
                'user_id' => $otherStudent->id,
                'assessment_version_id' => $draft->id,
            ]);

        // Assert (HTTP): the request still succeeds for the authenticated student
        $this->assertSame(201, $response->getStatusCode(), 'البدء يجب أن ينجح بغض النظر عن محتوى الطلب.');

        // Assert (DB): the session belongs to the authenticated student on the published version
        $this->assertSame($published->id, $response->json('data.assessment_version_id'));
        $this->assertDatabaseHas('assessment_sessions', [
            'id' => $response->json('data.session_id'),
            'user_id' => $student->id,
            'assessment_version_id' => $published->id,
            'status' => 'in_progress',
        ]);
        // لا يجب أن تنشأ أي جلسة مملوكة للمستخدم المُرسل داخل الطلب
        $this->assertDatabaseMissing('assessment_sessions', [
            'user_id' => $otherStudent->id,
        ]);
    }

    // ===============================================================
    // 3-6. No valid published version must ever start a session
    // ===============================================================

    public function test_start_without_any_version_returns_assessment_unavailable(): void
    {
        // Arrange: no assessment version exists at all
        $student = $this->createStudentUser();

        // Act
        $response = $this->actingAs($student)
            ->postJson(route('assessment.sessions.store'));

        // Assert (HTTP)
        $this->assertSame(409, $response->getStatusCode(), 'غياب أي إصدار يجب أن يعيد 409.');
        $response->assertJson([
            'message' => 'لا يتوفر اختبار التقييم حالياً. برجاء المحاولة لاحقاً.',
            'code' => 'ASSESSMENT_UNAVAILABLE',
        ]);

        // Assert (DB)
        $this->assertSame(0, AssessmentSession::count(), 'لا يجب أن تُنشأ أي جلسة دون إصدار منشور.');
    }

    public function test_start_ignores_a_retired_version(): void
    {
        // Arrange: a retired version still carries published_at, but its status is invalid
        $student = $this->createStudentUser();
        $this->createRetiredVersion(1);

        // Act
        $response = $this->actingAs($student)
            ->postJson(route('assessment.sessions.store'));

        // Assert (HTTP)
        $this->assertSame(409, $response->getStatusCode(), 'الإصدار المتقاعد غير صالح للاستخدام ويجب أن يعيد 409.');
        $response->assertJson(['code' => 'ASSESSMENT_UNAVAILABLE']);

        // Assert (DB)
        $this->assertSame(0, AssessmentSession::count(), 'لا يجب أن تُنشأ جلسة لإصدار متقاعد.');
    }

    public function test_start_ignores_an_active_version_without_published_at(): void
    {
        // Arrange: active but never published
        $student = $this->createStudentUser();
        $this->createActiveUnpublishedVersion(1);

        // Act
        $response = $this->actingAs($student)
            ->postJson(route('assessment.sessions.store'));

        // Assert (HTTP)
        $this->assertSame(409, $response->getStatusCode(), 'الإصدار النشط غير المنشور يجب أن يعيد 409.');
        $response->assertJson(['code' => 'ASSESSMENT_UNAVAILABLE']);

        // Assert (DB)
        $this->assertSame(0, AssessmentSession::count(), 'لا يجب أن تُنشأ جلسة لإصدار غير منشور.');
    }

    public function test_start_with_multiple_published_versions_returns_assessment_unavailable(): void
    {
        // Arrange (data integrity): two versions qualify as published — an ambiguous state
        $student = $this->createStudentUser();
        $first = $this->createPublishedAssessmentVersion(1);
        $second = $this->createPublishedAssessmentVersion(2);

        // Act
        $response = $this->actingAs($student)
            ->postJson(route('assessment.sessions.store'));

        // Assert (HTTP): the conflict is never resolved by silently picking one
        $this->assertSame(409, $response->getStatusCode(), 'تعدد الإصدارات المنشورة خلل سلامة بيانات يجب أن يعيد 409.');
        $response->assertJson(['code' => 'ASSESSMENT_UNAVAILABLE']);

        // Assert (DB): no session created, and no version row mutated while detecting the conflict
        $this->assertSame(0, AssessmentSession::count(), 'لا يجب أن تُنشأ جلسة في حال تعارض الإصدارات.');
        $this->assertSame('active', AssessmentVersion::find($first->id)->status);
        $this->assertSame('active', AssessmentVersion::find($second->id)->status);
    }

    // ===============================================================
    // 7-8. Access control on the start endpoint
    // ===============================================================

    public function test_guest_without_json_accept_is_redirected_to_login(): void
    {
        // Act: a browser-style (non-JSON) POST from a guest
        $response = $this->post(route('assessment.sessions.store'));

        // Assert (HTTP): guests are redirected, per the session-authentication model
        $this->assertSame(302, $response->getStatusCode(), 'الزائر يُحوّل إلى تسجيل الدخول في الطلبات غير JSON.');
        $response->assertRedirect(route('login'));

        // Assert (DB)
        $this->assertSame(0, AssessmentSession::count(), 'لا يجب أن تُنشأ جلسة لزائر.');
    }

    public function test_admin_role_cannot_start_a_student_session(): void
    {
        // Arrange: an authenticated user whose role is not student
        $admin = $this->createAdminUser();
        $this->createPublishedAssessmentVersion(1);

        // Act
        $response = $this->actingAs($admin)
            ->postJson(route('assessment.sessions.store'));

        // Assert (HTTP): the role guard rejects with the approved forbidden envelope
        $this->assertSame(403, $response->getStatusCode(), 'الدور غير المصرّح يجب أن يعيد 403.');
        $response->assertJson([
            'message' => 'لا تملك الصلاحية للقيام بهذه العملية.',
            'code' => 'FORBIDDEN',
        ]);

        // Assert (DB)
        $this->assertSame(0, AssessmentSession::count(), 'لا يجب أن تُنشأ جلسة لدور غير الطالب.');
    }
}
