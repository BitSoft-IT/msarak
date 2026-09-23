<?php

namespace Tests\Feature\Admin;

use App\Models\AssessmentSession;
use App\Models\AssessmentVersion;
use App\Models\Result;
use App\Models\ResultRecommendation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatisticsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private AssessmentVersion $version;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->version = AssessmentVersion::create([
            'version_number' => 1,
            'status' => 'active',
            'published_at' => now(),
        ]);
    }

    public function test_statistics_are_aggregated_from_source_tables_without_student_data(): void
    {
        $first = $this->makeSession('2026-09-05 08:00:00', '2026-09-05 09:00:00');
        $second = $this->makeSession('2026-09-10 08:00:00', '2026-09-11 09:00:00');
        $this->makeSession('2026-09-15 08:00:00');
        $this->makeSession('2026-08-20 08:00:00', '2026-08-21 09:00:00');

        $this->addRecommendations($first, '2026-09-05 09:00:00', [
            ['computer_science', 'علوم الحاسوب وتقنية المعلومات'],
            ['accounting', 'المحاسبة'],
        ]);
        $this->addRecommendations($second, '2026-09-11 09:00:00', [
            ['computer_science', 'علوم الحاسوب وتقنية المعلومات'],
        ]);

        $response = $this->actingAs($this->admin)->getJson(
            route('admin.statistics.index', ['from' => '2026-09-01', 'to' => '2026-09-30']),
        );

        $response->assertOk()
            ->assertJsonPath('data.started_assessments', 3)
            ->assertJsonPath('data.completed_assessments', 2)
            ->assertJsonPath('data.completion_rate', 66.7)
            ->assertJsonPath('data.top_recommendations.0.specialization_key', 'computer_science')
            ->assertJsonPath('data.top_recommendations.0.count', 2);

        $payload = $response->json('data');
        $this->assertArrayNotHasKey('users', $payload);
        $this->assertArrayNotHasKey('answers', $payload);
        $this->assertArrayNotHasKey('user_id', $payload);
    }

    public function test_statistics_date_validation_rejects_an_invalid_or_reversed_range(): void
    {
        $this->actingAs($this->admin)
            ->getJson(route('admin.statistics.index', ['from' => 'not-a-date']))
            ->assertUnprocessable();

        $this->actingAs($this->admin)
            ->getJson(route('admin.statistics.index', ['from' => '2026-09-20', 'to' => '2026-09-01']))
            ->assertUnprocessable();
    }

    public function test_empty_statistics_return_zero_without_division_errors(): void
    {
        $this->actingAs($this->admin)
            ->getJson(route('admin.statistics.index'))
            ->assertOk()
            ->assertJsonPath('data.started_assessments', 0)
            ->assertJsonPath('data.completed_assessments', 0)
            ->assertJsonPath('data.completion_rate', 0)
            ->assertJsonPath('data.top_recommendations', []);
    }

    private function makeSession(string $startedAt, ?string $completedAt = null): AssessmentSession
    {
        return AssessmentSession::create([
            'user_id' => User::factory()->create(['role' => 'student'])->id,
            'assessment_version_id' => $this->version->id,
            'status' => $completedAt ? 'completed' : 'in_progress',
            'started_at' => $startedAt,
            'completed_at' => $completedAt,
        ]);
    }

    /** @param array<int, array{string, string}> $recommendations */
    private function addRecommendations(AssessmentSession $session, string $createdAt, array $recommendations): void
    {
        $result = Result::create([
            'assessment_session_id' => $session->id,
            'catalog_version' => '1.0',
            'scoring_version' => '1.2',
        ]);
        $result->forceFill(['created_at' => $createdAt])->saveQuietly();

        foreach ($recommendations as $index => [$key, $name]) {
            ResultRecommendation::create([
                'result_id' => $result->id,
                'specialization_key' => $key,
                'name_snapshot' => $name,
                'display_order' => $index + 1,
                'similarity_score' => 0.90 - ($index * 0.1),
                'rationale_snapshot' => 'سبب محفوظ',
            ]);
        }
    }
}
