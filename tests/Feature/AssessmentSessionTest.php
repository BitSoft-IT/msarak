<?php

namespace Tests\Feature;

use App\Models\Answer;
use App\Models\AnswerOptionRating;
use App\Models\AssessmentSession;
use App\Models\AssessmentVersion;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Result;
use App\Models\User;
use App\Services\AssessmentSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AssessmentSessionTest extends TestCase
{
    use RefreshDatabase;

    private const RIASEC_CODES = ['R', 'I', 'A', 'S', 'E', 'C'];

    private User $student;

    private AssessmentVersion $version;

    protected function setUp(): void
    {
        parent::setUp();

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
                'scenario' => "سيناريو الموقف {$p} للإصدار {$versionNumber}",
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

    // ===============================================================
    // 1. Session Lifecycle Tests
    // ===============================================================

    public function test_guest_cannot_start_assessment(): void
    {
        $response = $this->postJson(route('assessment.sessions.store'));

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_start_assessment(): void
    {
        $response = $this->actingAs($this->student)
            ->postJson(route('assessment.sessions.store'));

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'session_id',
                    'status',
                    'assessment_version_id',
                    'resumed',
                    'redirect_url',
                ],
                'message',
            ]);

        $sessionId = $response->json('data.session_id');

        $this->assertDatabaseHas('assessment_sessions', [
            'id' => $sessionId,
            'user_id' => $this->student->id,
            'assessment_version_id' => $this->version->id,
            'status' => 'in_progress',
        ]);

        $this->assertSame(false, $response->json('data.resumed'));
        $this->assertSame("/assessment/sessions/{$sessionId}", $response->json('data.redirect_url'));
        $this->assertSame('بدأ التقييم.', $response->json('message'));
    }

    public function test_published_assessment_version_is_used(): void
    {
        // Version 1 is created in setUp as active/published.
        $response = $this->actingAs($this->student)
            ->postJson(route('assessment.sessions.store'));

        $response->assertStatus(201);
        $this->assertSame($this->version->id, $response->json('data.assessment_version_id'));
    }

    public function test_no_published_version_returns_safe_failure(): void
    {
        // Unpublish the version
        $this->version->update([
            'status' => 'draft',
            'published_at' => null,
        ]);

        $response = $this->actingAs($this->student)
            ->postJson(route('assessment.sessions.store'));

        $response->assertStatus(409)
            ->assertJson([
                'message' => 'لا يتوفر اختبار التقييم حالياً. برجاء المحاولة لاحقاً.',
                'code' => 'ASSESSMENT_UNAVAILABLE',
            ]);

        $this->assertSame(0, AssessmentSession::count());
    }

    public function test_existing_incomplete_session_is_reused(): void
    {
        // First start
        $firstResponse = $this->actingAs($this->student)
            ->postJson(route('assessment.sessions.store'));
        $firstResponse->assertStatus(201);
        $sessionId = $firstResponse->json('data.session_id');

        // Repeated start
        $secondResponse = $this->actingAs($this->student)
            ->postJson(route('assessment.sessions.store'));

        $secondResponse->assertStatus(200)
            ->assertJson([
                'data' => [
                    'session_id' => $sessionId,
                    'status' => 'in_progress',
                    'assessment_version_id' => $this->version->id,
                    'resumed' => true,
                    'redirect_url' => "/assessment/sessions/{$sessionId}",
                ],
                'message' => 'تم استئناف التقييم.',
            ]);

        $this->assertSame(1, AssessmentSession::where('user_id', $this->student->id)->count());
    }

    public function test_repeated_start_does_not_create_duplicate_session(): void
    {
        $this->actingAs($this->student)->postJson(route('assessment.sessions.store'));
        $this->actingAs($this->student)->postJson(route('assessment.sessions.store'));
        $this->actingAs($this->student)->postJson(route('assessment.sessions.store'));

        $this->assertSame(1, AssessmentSession::where('user_id', $this->student->id)->count());
    }

    public function test_concurrent_start_simulation_maintains_single_incomplete_session_invariant(): void
    {
        // Simulate two start requests for the same user and published version
        $service = app(AssessmentSessionService::class);

        $result1 = $service->startOrResumeSession($this->student);
        $this->assertFalse($result1['resumed']);

        // Second call for the same user resolves to the existing session
        $result2 = $service->startOrResumeSession($this->student);
        $this->assertTrue($result2['resumed']);
        $this->assertSame($result1['session']->id, $result2['session']->id);

        // Database invariant: exactly one incomplete session exists for this user + version
        $incompleteSessionsCount = AssessmentSession::where('user_id', $this->student->id)
            ->where('assessment_version_id', $this->version->id)
            ->where('status', 'in_progress')
            ->count();

        $this->assertSame(1, $incompleteSessionsCount);
    }

    public function test_session_remains_pinned_to_original_version(): void
    {
        // Student starts on version 1
        $startResponse = $this->actingAs($this->student)
            ->postJson(route('assessment.sessions.store'));
        $sessionId = $startResponse->json('data.session_id');

        // Later, version 1 is retired and version 2 is published
        $this->version->update(['status' => 'retired']);
        $version2 = $this->createPublishedVersion(2);

        // Resume session
        $resumeResponse = $this->actingAs($this->student)
            ->getJson(route('assessment.show', $sessionId));

        $resumeResponse->assertStatus(200);
        $this->assertSame($this->version->id, $resumeResponse->json('data.session.assessment_version_id'));

        // Questions in the resumed session belong to version 1
        $returnedQuestionIds = collect($resumeResponse->json('data.questions'))->pluck('question_id');
        $version1QuestionIds = $this->version->questions()->pluck('id');
        $this->assertEqualsCanonicalizing($version1QuestionIds->all(), $returnedQuestionIds->all());
    }

    // ===============================================================
    // 2. Ownership Tests
    // ===============================================================

    public function test_user_cannot_resume_another_users_session(): void
    {
        $otherStudent = User::factory()->create(['role' => 'student']);
        $otherSession = AssessmentSession::create([
            'user_id' => $otherStudent->id,
            'assessment_version_id' => $this->version->id,
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        $response = $this->actingAs($this->student)
            ->getJson(route('assessment.show', $otherSession->id));

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'المورد غير موجود.',
                'code' => 'RESOURCE_NOT_FOUND',
            ]);
    }

    public function test_user_cannot_save_into_another_users_session(): void
    {
        $otherStudent = User::factory()->create(['role' => 'student']);
        $otherSession = AssessmentSession::create([
            'user_id' => $otherStudent->id,
            'assessment_version_id' => $this->version->id,
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        $question = $this->version->questions()->first();
        $option = $question->questionOptions()->first();

        $response = $this->actingAs($this->student)
            ->putJson(route('assessment.answers.update', [
                'assessmentSession' => $otherSession->id,
                'question' => $question->id,
            ]), [
                'primary_option_id' => $option->id,
                'none_selected' => false,
                'unable_to_judge' => false,
            ]);

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'المورد غير موجود.',
                'code' => 'RESOURCE_NOT_FOUND',
            ]);

        $this->assertSame(0, Answer::where('assessment_session_id', $otherSession->id)->count());
    }

    public function test_user_cannot_update_another_users_answer(): void
    {
        $otherStudent = User::factory()->create(['role' => 'student']);
        $otherSession = AssessmentSession::create([
            'user_id' => $otherStudent->id,
            'assessment_version_id' => $this->version->id,
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        $question = $this->version->questions()->first();
        $optionA = $question->questionOptions()->first();
        $optionB = $question->questionOptions()->skip(1)->first();

        // Other student already has an answer
        $answer = Answer::create([
            'assessment_session_id' => $otherSession->id,
            'question_id' => $question->id,
            'primary_option_id' => $optionA->id,
            'response_type' => 'option',
        ]);

        // Attacker attempts to update it
        $response = $this->actingAs($this->student)
            ->putJson(route('assessment.answers.update', [
                'assessmentSession' => $otherSession->id,
                'question' => $question->id,
            ]), [
                'primary_option_id' => $optionB->id,
                'none_selected' => false,
                'unable_to_judge' => false,
            ]);

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'المورد غير موجود.',
                'code' => 'RESOURCE_NOT_FOUND',
            ]);

        $this->assertSame($optionA->id, $answer->fresh()->primary_option_id);
    }

    // ===============================================================
    // 3. Saving Answers Tests
    // ===============================================================

    public function test_valid_primary_option_is_saved(): void
    {
        $session = AssessmentSession::create([
            'user_id' => $this->student->id,
            'assessment_version_id' => $this->version->id,
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        $question = $this->version->questions()->first();
        $option = $question->questionOptions()->first();

        $response = $this->actingAs($this->student)
            ->putJson(route('assessment.answers.update', [
                'assessmentSession' => $session->id,
                'question' => $question->id,
            ]), [
                'primary_option_id' => $option->id,
                'none_selected' => false,
                'unable_to_judge' => false,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'question_id' => $question->id,
                    'saved' => true,
                    'answer' => [
                        'primary_option_id' => $option->id,
                        'none_selected' => false,
                        'unable_to_judge' => false,
                        'ratings' => [],
                    ],
                    'progress' => [
                        'processed' => 1,
                        'total' => 18,
                    ],
                ],
                'message' => 'حُفظت الإجابة.',
            ]);

        $this->assertDatabaseHas('answers', [
            'assessment_session_id' => $session->id,
            'question_id' => $question->id,
            'primary_option_id' => $option->id,
            'response_type' => 'option',
        ]);
    }

    public function test_option_from_another_question_is_rejected(): void
    {
        $session = AssessmentSession::create([
            'user_id' => $this->student->id,
            'assessment_version_id' => $this->version->id,
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        $question1 = $this->version->questions()->where('position', 1)->first();
        $question2 = $this->version->questions()->where('position', 2)->first();
        $foreignOption = $question2->questionOptions()->first();

        $response = $this->actingAs($this->student)
            ->putJson(route('assessment.answers.update', [
                'assessmentSession' => $session->id,
                'question' => $question1->id,
            ]), [
                'primary_option_id' => $foreignOption->id,
                'none_selected' => false,
                'unable_to_judge' => false,
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'بيانات الإجابة غير صالحة.',
                'code' => 'ANSWER_INVALID',
            ]);

        $this->assertSame(0, Answer::where('assessment_session_id', $session->id)->count());
    }

    public function test_question_from_another_version_is_rejected(): void
    {
        $session = AssessmentSession::create([
            'user_id' => $this->student->id,
            'assessment_version_id' => $this->version->id,
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        // Create another version
        $version2 = AssessmentVersion::create([
            'version_number' => 2,
            'status' => 'draft',
        ]);
        $questionV2 = Question::create([
            'assessment_version_id' => $version2->id,
            'position' => 1,
            'scenario' => 'موقف من إصدار آخر',
        ]);
        $optV2 = QuestionOption::create([
            'question_id' => $questionV2->id,
            'position' => 1,
            'option_text' => 'خيار',
            'riasec_code' => 'R',
        ]);

        $response = $this->actingAs($this->student)
            ->putJson(route('assessment.answers.update', [
                'assessmentSession' => $session->id,
                'question' => $questionV2->id,
            ]), [
                'primary_option_id' => $optV2->id,
                'none_selected' => false,
                'unable_to_judge' => false,
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'بيانات الإجابة غير صالحة.',
                'code' => 'ANSWER_INVALID',
            ]);

        $this->assertSame(0, Answer::where('assessment_session_id', $session->id)->count());
    }

    // ===============================================================
    // 4. Ratings Tests
    // ===============================================================

    public function test_valid_ratings_are_saved(): void
    {
        $session = AssessmentSession::create([
            'user_id' => $this->student->id,
            'assessment_version_id' => $this->version->id,
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        $question = $this->version->questions()->first();
        $options = $question->questionOptions()->take(3)->get();

        $response = $this->actingAs($this->student)
            ->putJson(route('assessment.answers.update', [
                'assessmentSession' => $session->id,
                'question' => $question->id,
            ]), [
                'primary_option_id' => $options[0]->id,
                'none_selected' => false,
                'unable_to_judge' => false,
                'ratings' => [
                    ['option_id' => $options[0]->id, 'rating' => 2],
                    ['option_id' => $options[1]->id, 'rating' => 0],
                    ['option_id' => $options[2]->id, 'rating' => -2],
                ],
            ]);

        $response->assertStatus(200);

        $answer = Answer::where('assessment_session_id', $session->id)->first();
        $this->assertNotNull($answer);
        $this->assertSame(3, $answer->optionRatings()->count());

        $this->assertDatabaseHas('answer_option_ratings', [
            'answer_id' => $answer->id,
            'question_option_id' => $options[0]->id,
            'rating' => 2,
        ]);
        $this->assertDatabaseHas('answer_option_ratings', [
            'answer_id' => $answer->id,
            'question_option_id' => $options[1]->id,
            'rating' => 0,
        ]);
        $this->assertDatabaseHas('answer_option_ratings', [
            'answer_id' => $answer->id,
            'question_option_id' => $options[2]->id,
            'rating' => -2,
        ]);
    }

    public function test_missing_rating_remains_missing(): void
    {
        $session = AssessmentSession::create([
            'user_id' => $this->student->id,
            'assessment_version_id' => $this->version->id,
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        $question = $this->version->questions()->first();
        $options = $question->questionOptions;

        // Rate only the 1st option
        $response = $this->actingAs($this->student)
            ->putJson(route('assessment.answers.update', [
                'assessmentSession' => $session->id,
                'question' => $question->id,
            ]), [
                'primary_option_id' => $options[0]->id,
                'none_selected' => false,
                'unable_to_judge' => false,
                'ratings' => [
                    ['option_id' => $options[0]->id, 'rating' => 1],
                ],
            ]);

        $response->assertStatus(200);

        $answer = Answer::where('assessment_session_id', $session->id)->first();
        $this->assertSame(1, $answer->optionRatings()->count());

        // The remaining 3 options must NOT have a rating row (and must not be set to 0)
        $this->assertDatabaseMissing('answer_option_ratings', [
            'answer_id' => $answer->id,
            'question_option_id' => $options[1]->id,
        ]);
    }

    public function test_value_below_minus_two_rejected(): void
    {
        $session = AssessmentSession::create([
            'user_id' => $this->student->id,
            'assessment_version_id' => $this->version->id,
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        $question = $this->version->questions()->first();
        $option = $question->questionOptions()->first();

        $response = $this->actingAs($this->student)
            ->putJson(route('assessment.answers.update', [
                'assessmentSession' => $session->id,
                'question' => $question->id,
            ]), [
                'primary_option_id' => $option->id,
                'none_selected' => false,
                'unable_to_judge' => false,
                'ratings' => [
                    ['option_id' => $option->id, 'rating' => -3],
                ],
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'بيانات الإجابة غير صالحة.',
                'code' => 'ANSWER_INVALID',
            ]);
    }

    public function test_value_above_two_rejected(): void
    {
        $session = AssessmentSession::create([
            'user_id' => $this->student->id,
            'assessment_version_id' => $this->version->id,
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        $question = $this->version->questions()->first();
        $option = $question->questionOptions()->first();

        $response = $this->actingAs($this->student)
            ->putJson(route('assessment.answers.update', [
                'assessmentSession' => $session->id,
                'question' => $question->id,
            ]), [
                'primary_option_id' => $option->id,
                'none_selected' => false,
                'unable_to_judge' => false,
                'ratings' => [
                    ['option_id' => $option->id, 'rating' => 3],
                ],
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'بيانات الإجابة غير صالحة.',
                'code' => 'ANSWER_INVALID',
            ]);
    }

    public function test_option_from_another_question_rejected_in_ratings(): void
    {
        $session = AssessmentSession::create([
            'user_id' => $this->student->id,
            'assessment_version_id' => $this->version->id,
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        $question1 = $this->version->questions()->where('position', 1)->first();
        $question2 = $this->version->questions()->where('position', 2)->first();
        $option1 = $question1->questionOptions()->first();
        $foreignOption = $question2->questionOptions()->first();

        $response = $this->actingAs($this->student)
            ->putJson(route('assessment.answers.update', [
                'assessmentSession' => $session->id,
                'question' => $question1->id,
            ]), [
                'primary_option_id' => $option1->id,
                'none_selected' => false,
                'unable_to_judge' => false,
                'ratings' => [
                    ['option_id' => $foreignOption->id, 'rating' => 1],
                ],
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'بيانات الإجابة غير صالحة.',
                'code' => 'ANSWER_INVALID',
            ]);
    }

    public function test_duplicate_option_rating_rejected(): void
    {
        $session = AssessmentSession::create([
            'user_id' => $this->student->id,
            'assessment_version_id' => $this->version->id,
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        $question = $this->version->questions()->first();
        $option = $question->questionOptions()->first();

        $response = $this->actingAs($this->student)
            ->putJson(route('assessment.answers.update', [
                'assessmentSession' => $session->id,
                'question' => $question->id,
            ]), [
                'primary_option_id' => $option->id,
                'none_selected' => false,
                'unable_to_judge' => false,
                'ratings' => [
                    ['option_id' => $option->id, 'rating' => 1],
                    ['option_id' => $option->id, 'rating' => -1],
                ],
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'بيانات الإجابة غير صالحة.',
                'code' => 'ANSWER_INVALID',
            ]);
    }

    // ===============================================================
    // 5. Special Responses Tests
    // ===============================================================

    public function test_none_selected_is_stored_as_processed_answer(): void
    {
        $session = AssessmentSession::create([
            'user_id' => $this->student->id,
            'assessment_version_id' => $this->version->id,
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        $question = $this->version->questions()->first();

        $response = $this->actingAs($this->student)
            ->putJson(route('assessment.answers.update', [
                'assessmentSession' => $session->id,
                'question' => $question->id,
            ]), [
                'primary_option_id' => null,
                'none_selected' => true,
                'unable_to_judge' => false,
                'ratings' => [],
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'question_id' => $question->id,
                    'saved' => true,
                    'answer' => [
                        'primary_option_id' => null,
                        'none_selected' => true,
                        'unable_to_judge' => false,
                        'ratings' => [],
                    ],
                    'progress' => [
                        'processed' => 1,
                        'total' => 18,
                    ],
                ],
                'message' => 'حُفظت الإجابة.',
            ]);

        $this->assertDatabaseHas('answers', [
            'assessment_session_id' => $session->id,
            'question_id' => $question->id,
            'primary_option_id' => null,
            'response_type' => 'none',
        ]);
    }

    public function test_unable_to_judge_rejects_primary_option(): void
    {
        $session = AssessmentSession::create([
            'user_id' => $this->student->id,
            'assessment_version_id' => $this->version->id,
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        $question = $this->version->questions()->first();
        $option = $question->questionOptions()->first();

        $response = $this->actingAs($this->student)
            ->putJson(route('assessment.answers.update', [
                'assessmentSession' => $session->id,
                'question' => $question->id,
            ]), [
                'primary_option_id' => $option->id,
                'none_selected' => false,
                'unable_to_judge' => true,
                'ratings' => [],
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'بيانات الإجابة غير صالحة.',
                'code' => 'ANSWER_INVALID',
            ]);
    }

    public function test_unable_to_judge_rejects_ratings(): void
    {
        $session = AssessmentSession::create([
            'user_id' => $this->student->id,
            'assessment_version_id' => $this->version->id,
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        $question = $this->version->questions()->first();
        $option = $question->questionOptions()->first();

        $response = $this->actingAs($this->student)
            ->putJson(route('assessment.answers.update', [
                'assessmentSession' => $session->id,
                'question' => $question->id,
            ]), [
                'primary_option_id' => null,
                'none_selected' => false,
                'unable_to_judge' => true,
                'ratings' => [
                    ['option_id' => $option->id, 'rating' => 1],
                ],
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'بيانات الإجابة غير صالحة.',
                'code' => 'ANSWER_INVALID',
            ]);
    }

    public function test_unable_to_judge_is_stored_as_processed_answer(): void
    {
        $session = AssessmentSession::create([
            'user_id' => $this->student->id,
            'assessment_version_id' => $this->version->id,
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        $question = $this->version->questions()->first();

        $response = $this->actingAs($this->student)
            ->putJson(route('assessment.answers.update', [
                'assessmentSession' => $session->id,
                'question' => $question->id,
            ]), [
                'primary_option_id' => null,
                'none_selected' => false,
                'unable_to_judge' => true,
                'ratings' => [],
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'question_id' => $question->id,
                    'saved' => true,
                    'answer' => [
                        'primary_option_id' => null,
                        'none_selected' => false,
                        'unable_to_judge' => true,
                        'ratings' => [],
                    ],
                ],
            ]);

        $this->assertDatabaseHas('answers', [
            'assessment_session_id' => $session->id,
            'question_id' => $question->id,
            'primary_option_id' => null,
            'response_type' => 'cannot_judge',
        ]);
    }

    // ===============================================================
    // 6. Update Answers Tests
    // ===============================================================

    public function test_saving_same_question_updates_existing_answer(): void
    {
        $session = AssessmentSession::create([
            'user_id' => $this->student->id,
            'assessment_version_id' => $this->version->id,
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        $question = $this->version->questions()->first();
        $optionA = $question->questionOptions()->first();
        $optionB = $question->questionOptions()->skip(1)->first();

        // Save 1
        $this->actingAs($this->student)
            ->putJson(route('assessment.answers.update', [
                'assessmentSession' => $session->id,
                'question' => $question->id,
            ]), [
                'primary_option_id' => $optionA->id,
                'none_selected' => false,
                'unable_to_judge' => false,
            ])->assertStatus(200);

        // Save 2
        $response = $this->actingAs($this->student)
            ->putJson(route('assessment.answers.update', [
                'assessmentSession' => $session->id,
                'question' => $question->id,
            ]), [
                'primary_option_id' => $optionB->id,
                'none_selected' => false,
                'unable_to_judge' => false,
            ]);

        $response->assertStatus(200);

        // Assert only one answer exists
        $this->assertSame(1, Answer::where('assessment_session_id', $session->id)->count());
        $this->assertDatabaseHas('answers', [
            'assessment_session_id' => $session->id,
            'question_id' => $question->id,
            'primary_option_id' => $optionB->id,
            'response_type' => 'option',
        ]);
    }

    public function test_ratings_are_synchronized_with_latest_save(): void
    {
        $session = AssessmentSession::create([
            'user_id' => $this->student->id,
            'assessment_version_id' => $this->version->id,
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        $question = $this->version->questions()->first();
        $options = $question->questionOptions;

        // Save 1: option A, ratings: [A=2, B=-1]
        $this->actingAs($this->student)
            ->putJson(route('assessment.answers.update', [
                'assessmentSession' => $session->id,
                'question' => $question->id,
            ]), [
                'primary_option_id' => $options[0]->id,
                'none_selected' => false,
                'unable_to_judge' => false,
                'ratings' => [
                    ['option_id' => $options[0]->id, 'rating' => 2],
                    ['option_id' => $options[1]->id, 'rating' => -1],
                ],
            ])->assertStatus(200);

        $answer = Answer::where('assessment_session_id', $session->id)->first();
        $this->assertSame(2, $answer->optionRatings()->count());

        // Save 2: option C, ratings: [C=1]
        $response = $this->actingAs($this->student)
            ->putJson(route('assessment.answers.update', [
                'assessmentSession' => $session->id,
                'question' => $question->id,
            ]), [
                'primary_option_id' => $options[2]->id,
                'none_selected' => false,
                'unable_to_judge' => false,
                'ratings' => [
                    ['option_id' => $options[2]->id, 'rating' => 1],
                ],
            ]);

        $response->assertStatus(200);

        // After Save 2, old ratings for A and B must NOT exist
        $this->assertSame(1, $answer->optionRatings()->count());
        $this->assertDatabaseMissing('answer_option_ratings', [
            'answer_id' => $answer->id,
            'question_option_id' => $options[0]->id,
        ]);
        $this->assertDatabaseMissing('answer_option_ratings', [
            'answer_id' => $answer->id,
            'question_option_id' => $options[1]->id,
        ]);
        $this->assertDatabaseHas('answer_option_ratings', [
            'answer_id' => $answer->id,
            'question_option_id' => $options[2]->id,
            'rating' => 1,
        ]);
    }

    public function test_changing_to_unable_to_judge_clears_primary_and_ratings(): void
    {
        $session = AssessmentSession::create([
            'user_id' => $this->student->id,
            'assessment_version_id' => $this->version->id,
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        $question = $this->version->questions()->first();
        $options = $question->questionOptions;

        // Save 1: option A with ratings
        $this->actingAs($this->student)
            ->putJson(route('assessment.answers.update', [
                'assessmentSession' => $session->id,
                'question' => $question->id,
            ]), [
                'primary_option_id' => $options[0]->id,
                'none_selected' => false,
                'unable_to_judge' => false,
                'ratings' => [
                    ['option_id' => $options[0]->id, 'rating' => 2],
                    ['option_id' => $options[1]->id, 'rating' => 1],
                ],
            ])->assertStatus(200);

        $answer = Answer::where('assessment_session_id', $session->id)->first();
        $this->assertSame(2, $answer->optionRatings()->count());

        // Save 2: change to unable_to_judge
        $response = $this->actingAs($this->student)
            ->putJson(route('assessment.answers.update', [
                'assessmentSession' => $session->id,
                'question' => $question->id,
            ]), [
                'primary_option_id' => null,
                'none_selected' => false,
                'unable_to_judge' => true,
                'ratings' => [],
            ]);

        $response->assertStatus(200);

        $answer->refresh();
        $this->assertNull($answer->primary_option_id);
        $this->assertSame('cannot_judge', $answer->response_type);
        $this->assertSame(0, $answer->optionRatings()->count());
    }

    // ===============================================================
    // 7. Idempotency Tests
    // ===============================================================

    public function test_replaying_identical_save_creates_no_duplicate_answer(): void
    {
        $session = AssessmentSession::create([
            'user_id' => $this->student->id,
            'assessment_version_id' => $this->version->id,
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        $question = $this->version->questions()->first();
        $option = $question->questionOptions()->first();

        $payload = [
            'primary_option_id' => $option->id,
            'none_selected' => false,
            'unable_to_judge' => false,
            'ratings' => [
                ['option_id' => $option->id, 'rating' => 2],
            ],
        ];

        // Send 1
        $r1 = $this->actingAs($this->student)
            ->putJson(route('assessment.answers.update', [
                'assessmentSession' => $session->id,
                'question' => $question->id,
            ]), $payload);
        $r1->assertStatus(200);

        // Send 2 (replay)
        $r2 = $this->actingAs($this->student)
            ->putJson(route('assessment.answers.update', [
                'assessmentSession' => $session->id,
                'question' => $question->id,
            ]), $payload);
        $r2->assertStatus(200);

        $this->assertSame(1, Answer::where('assessment_session_id', $session->id)->count());
        $this->assertSame(1, AnswerOptionRating::count());
        $this->assertSame(1, $r2->json('data.progress.processed'));
    }

    // ===============================================================
    // 8. Resume Tests
    // ===============================================================

    public function test_resume_returns_full_session_state_and_restores_answers(): void
    {
        $session = AssessmentSession::create([
            'user_id' => $this->student->id,
            'assessment_version_id' => $this->version->id,
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        $questions = $this->version->questions()->take(2)->get();
        $q1 = $questions[0];
        $q2 = $questions[1];

        // Answer Q1 with Primary and ratings
        $optQ1 = $q1->questionOptions()->first();
        $this->actingAs($this->student)
            ->putJson(route('assessment.answers.update', [
                'assessmentSession' => $session->id,
                'question' => $q1->id,
            ]), [
                'primary_option_id' => $optQ1->id,
                'none_selected' => false,
                'unable_to_judge' => false,
                'ratings' => [
                    ['option_id' => $optQ1->id, 'rating' => 2],
                ],
            ]);

        // Answer Q2 with unable_to_judge
        $this->actingAs($this->student)
            ->putJson(route('assessment.answers.update', [
                'assessmentSession' => $session->id,
                'question' => $q2->id,
            ]), [
                'primary_option_id' => null,
                'none_selected' => false,
                'unable_to_judge' => true,
                'ratings' => [],
            ]);

        // Resume session
        $response = $this->actingAs($this->student)
            ->getJson(route('assessment.show', $session->id));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'session' => [
                        'session_id',
                        'status',
                        'assessment_version_id',
                    ],
                    'progress' => [
                        'processed',
                        'total',
                        'current_position',
                    ],
                    'questions',
                    'saved_answers',
                ],
                'message',
            ]);

        $this->assertSame(2, $response->json('data.progress.processed'));
        $this->assertSame(18, $response->json('data.progress.total'));
        $this->assertSame(3, $response->json('data.progress.current_position'));

        $savedAnswers = $response->json('data.saved_answers');
        $this->assertCount(2, $savedAnswers);

        $savedQ1 = collect($savedAnswers)->firstWhere('question_id', $q1->id);
        $this->assertNotNull($savedQ1);
        $this->assertSame($optQ1->id, $savedQ1['primary_option_id']);
        $this->assertSame(false, $savedQ1['none_selected']);
        $this->assertSame(false, $savedQ1['unable_to_judge']);
        $this->assertCount(1, $savedQ1['ratings']);
        $this->assertSame(2, $savedQ1['ratings'][0]['rating']);

        $savedQ2 = collect($savedAnswers)->firstWhere('question_id', $q2->id);
        $this->assertNotNull($savedQ2);
        $this->assertNull($savedQ2['primary_option_id']);
        $this->assertSame(false, $savedQ2['none_selected']);
        $this->assertSame(true, $savedQ2['unable_to_judge']);
        $this->assertEmpty($savedQ2['ratings']);
    }

    // ===============================================================
    // 9. Failure and Atomic Transaction Tests
    // ===============================================================

    public function test_failed_save_rolls_back_all_changes(): void
    {
        $session = AssessmentSession::create([
            'user_id' => $this->student->id,
            'assessment_version_id' => $this->version->id,
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        $question = $this->version->questions()->first();
        $option = $question->questionOptions()->first();

        // Simulate failure by mocking or injecting a failure into the service save
        $this->mock(AssessmentSessionService::class, function ($mock) {
            $mock->shouldReceive('saveAnswer')
                ->once()
                ->andThrow(new \RuntimeException('Database disk failure simulation'));
        });

        $response = $this->actingAs($this->student)
            ->putJson(route('assessment.answers.update', [
                'assessmentSession' => $session->id,
                'question' => $question->id,
            ]), [
                'primary_option_id' => $option->id,
                'none_selected' => false,
                'unable_to_judge' => false,
            ]);

        $response->assertStatus(500)
            ->assertJson([
                'message' => 'تعذر حفظ الإجابة. حاول مرة أخرى.',
                'code' => 'ANSWER_SAVE_FAILED',
            ]);

        $this->assertSame(0, Answer::where('assessment_session_id', $session->id)->count());
    }

    public function test_failed_update_preserves_previous_successful_state(): void
    {
        $session = AssessmentSession::create([
            'user_id' => $this->student->id,
            'assessment_version_id' => $this->version->id,
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        $question = $this->version->questions()->first();
        $optionA = $question->questionOptions()->first();
        $optionB = $question->questionOptions()->skip(1)->first();

        // Initial successful save
        $this->actingAs($this->student)
            ->putJson(route('assessment.answers.update', [
                'assessmentSession' => $session->id,
                'question' => $question->id,
            ]), [
                'primary_option_id' => $optionA->id,
                'none_selected' => false,
                'unable_to_judge' => false,
                'ratings' => [
                    ['option_id' => $optionA->id, 'rating' => 2],
                ],
            ])->assertStatus(200);

        // Attempt update with invalid data (e.g. invalid rating)
        $failResponse = $this->actingAs($this->student)
            ->putJson(route('assessment.answers.update', [
                'assessmentSession' => $session->id,
                'question' => $question->id,
            ]), [
                'primary_option_id' => $optionB->id,
                'none_selected' => false,
                'unable_to_judge' => false,
                'ratings' => [
                    ['option_id' => $optionB->id, 'rating' => 999], // Invalid!
                ],
            ]);

        $failResponse->assertStatus(422);

        // Previous state is intact
        $answer = Answer::where('assessment_session_id', $session->id)->first();
        $this->assertSame($optionA->id, $answer->primary_option_id);
        $this->assertSame(1, $answer->optionRatings()->count());
        $this->assertSame(2, $answer->optionRatings()->first()->rating);
    }

    public function test_failed_save_does_not_change_progress(): void
    {
        $session = AssessmentSession::create([
            'user_id' => $this->student->id,
            'assessment_version_id' => $this->version->id,
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        $questions = $this->version->questions()->take(2)->get();
        $q1 = $questions[0];
        $q2 = $questions[1];
        $optQ1 = $q1->questionOptions()->first();
        $optQ2 = $q2->questionOptions()->first();

        // 1. Initial valid save for Question 1
        $r1 = $this->actingAs($this->student)
            ->putJson(route('assessment.answers.update', [
                'assessmentSession' => $session->id,
                'question' => $q1->id,
            ]), [
                'primary_option_id' => $optQ1->id,
                'none_selected' => false,
                'unable_to_judge' => false,
            ]);
        $r1->assertStatus(200);
        $this->assertSame(1, $r1->json('data.progress.processed'));

        // 2. Failed save for Question 2 (invalid rating)
        $r2 = $this->actingAs($this->student)
            ->putJson(route('assessment.answers.update', [
                'assessmentSession' => $session->id,
                'question' => $q2->id,
            ]), [
                'primary_option_id' => $optQ2->id,
                'none_selected' => false,
                'unable_to_judge' => false,
                'ratings' => [
                    ['option_id' => $optQ2->id, 'rating' => 5], // Invalid!
                ],
            ]);
        $r2->assertStatus(422);

        // 3. Progress remains 1, answer for Q2 was not saved
        $resumeResponse = $this->actingAs($this->student)
            ->getJson(route('assessment.show', $session->id));

        $resumeResponse->assertStatus(200);
        $this->assertSame(1, $resumeResponse->json('data.progress.processed'));
        $this->assertSame(1, Answer::where('assessment_session_id', $session->id)->count());
    }

    // ===============================================================
    // 10. Completed Session Guard Tests
    // ===============================================================

    public function test_cannot_save_answer_to_completed_session(): void
    {
        $session = AssessmentSession::create([
            'user_id' => $this->student->id,
            'assessment_version_id' => $this->version->id,
            'status' => 'completed',
            'started_at' => now()->subHour(),
            'completed_at' => now(),
        ]);

        $question = $this->version->questions()->first();
        $option = $question->questionOptions()->first();

        $response = $this->actingAs($this->student)
            ->putJson(route('assessment.answers.update', [
                'assessmentSession' => $session->id,
                'question' => $question->id,
            ]), [
                'primary_option_id' => $option->id,
                'none_selected' => false,
                'unable_to_judge' => false,
            ]);

        $response->assertStatus(409)
            ->assertJson([
                'message' => 'لا يمكن تعديل إجابات جلسة مكتملة.',
                'code' => 'SESSION_COMPLETED',
            ]);
    }

    // ===============================================================
    // 11. Forbidden Scope Guard Tests (No RIASEC code, No Result)
    // ===============================================================

    public function test_no_riasec_code_or_weights_in_resume_payload(): void
    {
        $session = AssessmentSession::create([
            'user_id' => $this->student->id,
            'assessment_version_id' => $this->version->id,
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        $response = $this->actingAs($this->student)
            ->getJson(route('assessment.show', $session->id));

        $response->assertStatus(200);

        $questions = $response->json('data.questions');
        foreach ($questions as $q) {
            $this->assertArrayNotHasKey('riasec_code', $q);
            $this->assertArrayNotHasKey('weight', $q);
            foreach ($q['options'] as $opt) {
                $this->assertArrayNotHasKey('riasec_code', $opt);
                $this->assertArrayNotHasKey('weight', $opt);
            }
        }
    }

    public function test_b03_does_not_create_result_or_riasec_calculation(): void
    {
        $session = AssessmentSession::create([
            'user_id' => $this->student->id,
            'assessment_version_id' => $this->version->id,
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        $question = $this->version->questions()->first();
        $option = $question->questionOptions()->first();

        $this->actingAs($this->student)
            ->putJson(route('assessment.answers.update', [
                'assessmentSession' => $session->id,
                'question' => $question->id,
            ]), [
                'primary_option_id' => $option->id,
                'none_selected' => false,
                'unable_to_judge' => false,
            ])->assertStatus(200);

        // Explicitly assert that B-03 creates NO Result records
        $this->assertSame(0, Result::count());
        $this->assertSame(0, DB::table('result_scores')->count());
        $this->assertSame(0, DB::table('result_recommendations')->count());
    }
}
