<?php

namespace Tests\Feature;

use App\Models\AssessmentSession;
use App\Models\AssessmentVersion;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssessmentUiTest extends TestCase
{
    use RefreshDatabase;

    private const RIASEC_CODES = ['R', 'I', 'A', 'S', 'E', 'C'];

    private User $student;

    private AssessmentVersion $version;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $this->student = User::factory()->create([
            'role' => 'student',
        ]);

        $this->version = $this->createPublishedVersion(1);
    }

    private function createPublishedVersion(int $versionNumber, int $questionCount = 18): AssessmentVersion
    {
        $version = AssessmentVersion::create([
            'version_number' => $versionNumber,
            'status' => 'active',
            'published_at' => now(),
        ]);

        for ($p = 1; $p <= $questionCount; $p++) {
            $question = Question::create([
                'assessment_version_id' => $version->id,
                'position' => $p,
                'scenario' => "سيناريو الموقف {$p} لاختبار الواجهة",
            ]);

            for ($opt = 1; $opt <= 4; $opt++) {
                QuestionOption::create([
                    'question_id' => $question->id,
                    'position' => $opt,
                    'option_text' => "نص الخيار {$opt} للموقف {$p}",
                    'riasec_code' => self::RIASEC_CODES[($opt - 1) % 6],
                ]);
            }
        }

        return $version;
    }

    public function test_guest_is_redirected_to_login_from_intro(): void
    {
        $response = $this->get(route('assessment.intro'));

        $response->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_to_login_from_assessment_show(): void
    {
        $session = AssessmentSession::create([
            'user_id' => $this->student->id,
            'assessment_version_id' => $this->version->id,
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        $response = $this->get(route('assessment.show', $session->id));

        $response->assertRedirect(route('login'));
    }

    public function test_student_can_view_assessment_intro_without_active_session(): void
    {
        $response = $this->actingAs($this->student)->get(route('assessment.intro'));

        $response->assertStatus(200);
        $response->assertSee('استكشاف ميولك');
        $response->assertSee('بدء التقييم');
        $response->assertSee('طريقة الإجابة');
        $response->assertSee('لا يشبهني أي من هذه التصرفات');
        $response->assertSee('لا أستطيع الحكم على هذا الموقف');
        $response->assertDontSee('لديك تقييم غير مكتمل');
    }

    public function test_student_can_view_assessment_intro_with_active_session(): void
    {
        $session = AssessmentSession::create([
            'user_id' => $this->student->id,
            'assessment_version_id' => $this->version->id,
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        $response = $this->actingAs($this->student)->get(route('assessment.intro'));

        $response->assertStatus(200);
        $response->assertSee('لديك تقييم غير مكتمل');
        $response->assertSee('متابعة التقييم');
    }

    public function test_student_submitting_intro_form_starts_or_resumes_session_via_redirect(): void
    {
        $response = $this->actingAs($this->student)->post(route('assessment.sessions.store'));

        $this->assertDatabaseHas('assessment_sessions', [
            'user_id' => $this->student->id,
            'assessment_version_id' => $this->version->id,
            'status' => 'in_progress',
        ]);

        $session = AssessmentSession::where('user_id', $this->student->id)->first();

        $response->assertRedirect("/assessment/sessions/{$session->id}");
    }

    public function test_student_can_view_assessment_show_page_for_owned_session(): void
    {
        $session = AssessmentSession::create([
            'user_id' => $this->student->id,
            'assessment_version_id' => $this->version->id,
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        $response = $this->actingAs($this->student)->get(route('assessment.show', $session->id));

        $response->assertStatus(200);
        $response->assertSee('استكشاف ميولك');
        $response->assertSee('سيناريو الموقف 1 لاختبار الواجهة');
        $response->assertSee('نص الخيار 1 للموقف 1');
        $response->assertSee('يشبهني جدًا');
        $response->assertSee('لا يشبهني أي من هذه التصرفات');
        $response->assertSee('لا أستطيع الحكم على هذا الموقف');
        $response->assertSee('إكمال التقييم');

        // Security check: riasec codes or weights must not be leaked into HTML
        $response->assertDontSee('"riasec_code"');
        $response->assertDontSee('riasec_code');
    }

    public function test_student_cannot_view_another_students_assessment_show_page(): void
    {
        $otherStudent = User::factory()->create(['role' => 'student']);
        $otherSession = AssessmentSession::create([
            'user_id' => $otherStudent->id,
            'assessment_version_id' => $this->version->id,
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        $response = $this->actingAs($this->student)->get(route('assessment.show', $otherSession->id));

        $response->assertStatus(404);
    }

    public function test_api_json_still_works_on_assessment_show(): void
    {
        $session = AssessmentSession::create([
            'user_id' => $this->student->id,
            'assessment_version_id' => $this->version->id,
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        $response = $this->actingAs($this->student)->getJson(route('assessment.show', $session->id));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'session' => ['session_id', 'status', 'assessment_version_id'],
                'progress' => ['processed', 'total', 'current_position'],
                'questions',
                'saved_answers',
            ],
            'message',
        ]);
        $response->assertJsonPath('data.session.session_id', $session->id);
    }
}
