<?php

namespace Tests\Feature\Admin;

use App\Models\AssessmentSession;
use App\Models\AssessmentVersion;
use App\Models\Question;
use App\Models\Result;
use App\Models\ResultRecommendation;
use App\Models\User;
use App\Services\AssessmentVersionService;
use Database\Seeders\AssessmentQuestionBankSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use RuntimeException;
use Tests\TestCase;

class AssessmentVersionManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->student = User::factory()->create(['role' => 'student']);
    }

    public function test_admin_routes_reject_guests_and_students(): void
    {
        $this->postJson(route('admin.assessment-versions.store'))->assertUnauthorized();

        $this->actingAs($this->student)
            ->postJson(route('admin.assessment-versions.store'))
            ->assertForbidden()
            ->assertJsonPath('code', 'FORBIDDEN');

        $this->actingAs($this->student)
            ->getJson(route('admin.statistics.index'))
            ->assertForbidden();
    }

    public function test_admin_can_create_one_open_draft_and_repeated_request_reuses_it(): void
    {
        $first = $this->actingAs($this->admin)
            ->postJson(route('admin.assessment-versions.store'))
            ->assertCreated()
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.question_count', 0);

        $second = $this->actingAs($this->admin)
            ->postJson(route('admin.assessment-versions.store'))
            ->assertOk();

        $this->assertSame($first->json('data.id'), $second->json('data.id'));
        $this->assertSame(1, AssessmentVersion::where('status', 'draft')->count());
    }

    public function test_admin_can_clone_the_published_version_into_a_draft(): void
    {
        $this->seed(AssessmentQuestionBankSeeder::class);
        $source = AssessmentVersion::where('status', 'active')->firstOrFail();

        $response = $this->actingAs($this->admin)->postJson(
            route('admin.assessment-versions.store'),
            ['source_version_id' => $source->id],
        );

        $response->assertCreated()->assertJsonPath('data.question_count', 18);
        $draft = AssessmentVersion::findOrFail($response->json('data.id'));
        $this->assertSame(72, $draft->questions()->withCount('questionOptions')->get()->sum('question_options_count'));
        $this->assertSame('draft', $draft->status);
    }

    public function test_admin_can_create_replace_and_delete_a_complete_draft_question(): void
    {
        $draft = AssessmentVersion::create(['version_number' => 1, 'status' => 'draft']);
        $payload = $this->questionPayload(1, 'الموقف الأول');

        $create = $this->actingAs($this->admin)->postJson(
            route('admin.assessment-versions.questions.store', $draft),
            $payload,
        )->assertCreated();

        $question = Question::findOrFail($create->json('data.id'));
        $this->assertCount(4, $question->questionOptions);

        $payload['scenario'] = 'الموقف المعدل';
        $payload['position'] = 2;
        $this->actingAs($this->admin)->putJson(
            route('admin.assessment-versions.questions.update', [$draft, $question]),
            $payload,
        )->assertOk()->assertJsonPath('data.scenario', 'الموقف المعدل');

        $this->assertDatabaseHas('questions', ['id' => $question->id, 'position' => 2]);
        $this->assertSame(4, $question->fresh()->questionOptions()->count());

        $this->actingAs($this->admin)->deleteJson(
            route('admin.assessment-versions.questions.destroy', [$draft, $question]),
        )->assertNoContent();
        $this->assertDatabaseMissing('questions', ['id' => $question->id]);
    }

    public function test_question_contract_rejects_duplicate_codes_and_invalid_option_count(): void
    {
        $draft = AssessmentVersion::create(['version_number' => 1, 'status' => 'draft']);
        $payload = $this->questionPayload(1, 'موقف');
        $payload['options'][1]['riasec_code'] = $payload['options'][0]['riasec_code'];
        array_pop($payload['options']);

        $this->actingAs($this->admin)
            ->postJson(route('admin.assessment-versions.questions.store', $draft), $payload)
            ->assertUnprocessable();

        $this->assertSame(0, Question::count());
    }

    public function test_active_or_used_versions_cannot_be_modified(): void
    {
        $active = AssessmentVersion::create([
            'version_number' => 1,
            'status' => 'active',
            'published_at' => now(),
        ]);

        $this->actingAs($this->admin)
            ->postJson(route('admin.assessment-versions.questions.store', $active), $this->questionPayload(1, 'موقف'))
            ->assertStatus(409)
            ->assertJsonPath('code', 'VERSION_NOT_DRAFT');

        $draft = AssessmentVersion::create(['version_number' => 2, 'status' => 'draft']);
        AssessmentSession::create([
            'user_id' => $this->student->id,
            'assessment_version_id' => $draft->id,
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        $this->actingAs($this->admin)
            ->postJson(route('admin.assessment-versions.questions.store', $draft), $this->questionPayload(1, 'موقف'))
            ->assertStatus(409)
            ->assertJsonPath('code', 'VERSION_NOT_DRAFT');
    }

    public function test_publish_rejects_an_incomplete_draft_without_partial_state_change(): void
    {
        $active = AssessmentVersion::create([
            'version_number' => 1,
            'status' => 'active',
            'published_at' => now(),
        ]);
        $draft = AssessmentVersion::create(['version_number' => 2, 'status' => 'draft']);

        $this->actingAs($this->admin)
            ->postJson(route('admin.assessment-versions.publish', $draft))
            ->assertUnprocessable()
            ->assertJsonPath('code', 'VERSION_NOT_PUBLISHABLE');

        $this->assertSame('active', $active->fresh()->status);
        $this->assertSame('draft', $draft->fresh()->status);
    }

    public function test_publish_rejects_a_globally_unbalanced_riasec_mapping(): void
    {
        $this->seed(AssessmentQuestionBankSeeder::class);
        $active = AssessmentVersion::where('status', 'active')->firstOrFail();
        $draft = app(AssessmentVersionService::class)->createOrGetDraft($active->id)['version'];
        $question = $draft->questions()->with('questionOptions')->firstOrFail();
        $presentCodes = $question->questionOptions->pluck('riasec_code')->all();
        $replacementCode = collect(['R', 'I', 'A', 'S', 'E', 'C'])
            ->first(fn (string $code): bool => ! in_array($code, $presentCodes, true));
        $question->questionOptions->first()->update(['riasec_code' => $replacementCode]);

        $this->actingAs($this->admin)
            ->postJson(route('admin.assessment-versions.publish', $draft))
            ->assertUnprocessable()
            ->assertJsonPath('code', 'VERSION_NOT_PUBLISHABLE');

        $this->assertSame('active', $active->fresh()->status);
        $this->assertSame('draft', $draft->fresh()->status);
    }

    public function test_valid_publish_retires_the_previous_version_and_preserves_history(): void
    {
        $this->seed(AssessmentQuestionBankSeeder::class);
        $oldVersion = AssessmentVersion::where('status', 'active')->firstOrFail();
        $session = AssessmentSession::create([
            'user_id' => $this->student->id,
            'assessment_version_id' => $oldVersion->id,
            'status' => 'completed',
            'started_at' => now()->subHour(),
            'completed_at' => now(),
        ]);
        $result = Result::create([
            'assessment_session_id' => $session->id,
            'catalog_version' => '1.0',
            'scoring_version' => '1.2',
        ]);
        $recommendation = ResultRecommendation::create([
            'result_id' => $result->id,
            'specialization_key' => 'computer_science',
            'name_snapshot' => 'علوم الحاسوب وتقنية المعلومات',
            'display_order' => 1,
            'similarity_score' => 0.95,
            'rationale_snapshot' => 'نسخة تاريخية',
        ]);

        $draft = app(AssessmentVersionService::class)->createOrGetDraft($oldVersion->id)['version'];
        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.assessment-versions.publish', $draft))
            ->assertOk()
            ->assertJsonPath('data.status', 'active');

        $this->assertSame('retired', $oldVersion->fresh()->status);
        $this->assertSame($draft->id, $response->json('data.id'));
        $this->assertSame($oldVersion->id, $session->fresh()->assessment_version_id);
        $this->assertSame('علوم الحاسوب وتقنية المعلومات', $recommendation->fresh()->name_snapshot);
        $this->assertSame('نسخة تاريخية', $recommendation->fresh()->rationale_snapshot);
        $this->assertSame(1, Result::count());
    }

    public function test_repeated_publish_is_idempotent(): void
    {
        $this->seed(AssessmentQuestionBankSeeder::class);
        $source = AssessmentVersion::where('status', 'active')->firstOrFail();
        $draft = app(AssessmentVersionService::class)->createOrGetDraft($source->id)['version'];

        $first = $this->actingAs($this->admin)->postJson(route('admin.assessment-versions.publish', $draft));
        $second = $this->actingAs($this->admin)->postJson(route('admin.assessment-versions.publish', $draft));

        $first->assertOk();
        $second->assertOk()->assertJsonPath('data.id', $first->json('data.id'));
        $this->assertSame(1, AssessmentVersion::where('status', 'active')->count());
    }

    public function test_repeated_publish_does_not_hide_multiple_active_versions(): void
    {
        $first = AssessmentVersion::create([
            'version_number' => 1,
            'status' => 'active',
            'published_at' => now(),
        ]);
        AssessmentVersion::create([
            'version_number' => 2,
            'status' => 'active',
            'published_at' => now(),
        ]);

        $this->actingAs($this->admin)
            ->postJson(route('admin.assessment-versions.publish', $first))
            ->assertUnprocessable()
            ->assertJsonPath('code', 'VERSION_NOT_PUBLISHABLE');
    }

    public function test_publish_rolls_back_retirement_when_activation_fails(): void
    {
        $this->seed(AssessmentQuestionBankSeeder::class);
        $oldVersion = AssessmentVersion::where('status', 'active')->firstOrFail();
        $draft = app(AssessmentVersionService::class)->createOrGetDraft($oldVersion->id)['version'];
        $eventName = 'eloquent.updating: '.AssessmentVersion::class;
        Event::listen($eventName, static function (AssessmentVersion $version): void {
            if ($version->isDirty('status') && $version->status === 'active') {
                throw new RuntimeException('forced activation failure');
            }
        });

        try {
            app(AssessmentVersionService::class)->publish($draft);
            $this->fail('Publishing should have failed.');
        } catch (RuntimeException $exception) {
            $this->assertSame('forced activation failure', $exception->getMessage());
        } finally {
            Event::forget($eventName);
        }

        $this->assertSame('active', $oldVersion->fresh()->status);
        $this->assertSame('draft', $draft->fresh()->status);
    }

    /** @return array<string, mixed> */
    private function questionPayload(int $position, string $scenario): array
    {
        return [
            'position' => $position,
            'scenario' => $scenario,
            'options' => [
                ['position' => 1, 'text' => 'الخيار الواقعي', 'riasec_code' => 'R'],
                ['position' => 2, 'text' => 'الخيار البحثي', 'riasec_code' => 'I'],
                ['position' => 3, 'text' => 'الخيار الفني', 'riasec_code' => 'A'],
                ['position' => 4, 'text' => 'الخيار الاجتماعي', 'riasec_code' => 'S'],
            ],
        ];
    }
}
