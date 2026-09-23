<?php

namespace Tests\Feature;

use App\Models\AssessmentSession;
use App\Models\AssessmentVersion;
use App\Models\Result;
use App\Models\ResultRecommendation;
use App\Models\ResultScore;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class H04ProfileResultSecurityTest extends TestCase
{
    use RefreshDatabase;

    private AssessmentVersion $version;

    protected function setUp(): void
    {
        parent::setUp();

        $this->version = AssessmentVersion::create([
            'version_number' => 1,
            'status' => 'active',
            'published_at' => now(),
        ]);
    }

    private function createResultFor(
        User $user,
        string $createdAt
    ): Result {
        $session = AssessmentSession::create([
            'user_id' => $user->id,
            'assessment_version_id' => $this->version->id,
            'status' => 'completed',
            'started_at' => now()->subHour(),
            'completed_at' => now(),
        ]);

        $result = new Result([
            'assessment_session_id' => $session->id,
            'catalog_version' => 'v1',
            'scoring_version' => 'v1.2',
        ]);

        $result->created_at = $createdAt;
        $result->save();

        return $result;
    }

    public function test_guest_cannot_open_profile(): void
    {
        $this->getJson(route('profile.show'))
            ->assertStatus(401);
    }

    public function test_student_profile_contains_only_current_user(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
            'name' => 'Student A',
        ]);

        $otherStudent = User::factory()->create([
            'role' => 'student',
            'name' => 'Student B',
        ]);

        $response = $this->actingAs($student)
            ->getJson(route('profile.show'));

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $student->id,
                    'name' => 'Student A',
                    'email' => $student->email,
                ],
            ]);

        $this->assertNotSame(
            $otherStudent->id,
            $response->json('data.id')
        );
    }

    public function test_result_history_contains_only_current_users_results(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $otherStudent = User::factory()->create(['role' => 'student']);

        $ownResult = $this->createResultFor(
            $student,
            now()->subDay()->toDateTimeString()
        );

        $otherResult = $this->createResultFor(
            $otherStudent,
            now()->toDateTimeString()
        );

        $response = $this->actingAs($student)
            ->getJson(route('profile.results.index'));

        $response->assertStatus(200);

        $resultIds = collect($response->json('data'))
            ->pluck('result_id')
            ->all();

        $this->assertContains($ownResult->id, $resultIds);
        $this->assertNotContains($otherResult->id, $resultIds);
    }

    public function test_result_history_is_sorted_newest_first(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $olderResult = $this->createResultFor(
            $student,
            now()->subDays(2)->toDateTimeString()
        );

        $newerResult = $this->createResultFor(
            $student,
            now()->toDateTimeString()
        );

        $response = $this->actingAs($student)
            ->getJson(route('profile.results.index'));

        $response->assertStatus(200);

        $ids = collect($response->json('data'))
            ->pluck('result_id')
            ->values()
            ->all();

        $this->assertSame([
            $newerResult->id,
            $olderResult->id,
        ], $ids);
    }

    public function test_user_id_query_parameter_cannot_change_history_owner(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $otherStudent = User::factory()->create(['role' => 'student']);

        $ownResult = $this->createResultFor(
            $student,
            now()->subDay()->toDateTimeString()
        );

        $otherResult = $this->createResultFor(
            $otherStudent,
            now()->toDateTimeString()
        );

        $response = $this->actingAs($student)
            ->getJson(
                route('profile.results.index')
                .'?user_id='
                .$otherStudent->id
            );

        $response->assertStatus(200);

        $ids = collect($response->json('data'))
            ->pluck('result_id')
            ->all();

        $this->assertContains($ownResult->id, $ids);
        $this->assertNotContains($otherResult->id, $ids);
    }

    public function test_student_can_open_own_result(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $result = $this->createResultFor(
            $student,
            now()->toDateTimeString()
        );

        ResultScore::create([
            'result_id' => $result->id,
            'riasec_code' => 'R',
            'score' => 75.50,
        ]);

        ResultRecommendation::create([
            'result_id' => $result->id,
            'specialization_key' => 'computer-science',
            'name_snapshot' => 'علوم الحاسوب',
            'display_order' => 1,
            'similarity_score' => 82.25,
            'rationale_snapshot' => 'يتوافق مع نمط اهتماماتك المحفوظ وقت التقييم.',
        ]);

        $response = $this->actingAs($student)
            ->getJson(route('results.show', $result));

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'result_id' => $result->id,
                    'catalog_version' => 'v1',
                    'scoring_version' => 'v1.2',
                    'scores' => [
                        [
                            'riasec_code' => 'R',
                            'score' => 75.5,
                        ],
                    ],
                    'recommendations' => [
                        [
                            'specialization_key' => 'computer-science',
                            'name_snapshot' => 'علوم الحاسوب',
                            'display_order' => 1,
                            'similarity_score' => 82.25,
                            'rationale_snapshot' => 'يتوافق مع نمط اهتماماتك المحفوظ وقت التقييم.',
                        ],
                    ],
                ],
            ]);
    }

    public function test_student_cannot_open_another_users_result(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $otherStudent = User::factory()->create(['role' => 'student']);

        $otherResult = $this->createResultFor(
            $otherStudent,
            now()->toDateTimeString()
        );

        $response = $this->actingAs($student)
            ->getJson(route('results.show', $otherResult));

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'المورد غير موجود.',
                'code' => 'RESOURCE_NOT_FOUND',
            ]);
    }

    public function test_guest_cannot_open_result_history(): void
    {
        $this->getJson(route('profile.results.index'))
            ->assertStatus(401);
    }

    public function test_guest_cannot_open_result(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $result = $this->createResultFor(
            $student,
            now()->toDateTimeString()
        );

        $this->getJson(route('results.show', $result))
            ->assertStatus(401);
    }
}
