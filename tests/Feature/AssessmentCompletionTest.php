<?php

namespace Tests\Feature;

use App\Exceptions\SessionCompletedException;
use App\Models\Answer;
use App\Models\AssessmentSession;
use App\Models\AssessmentVersion;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Result;
use App\Models\ResultRecommendation;
use App\Models\User;
use App\Services\AssessmentSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use RuntimeException;
use Tests\TestCase;

class AssessmentCompletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_fifteen_valid_answers_create_one_atomic_result_with_six_scores(): void
    {
        [$student, $session] = $this->makeCompleteSession(15);

        $response = $this->actingAs($student)
            ->postJson(route('assessment.sessions.complete', $session));

        $response->assertOk()
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.session_id', $session->id);

        $result = Result::with(['resultScores', 'resultRecommendations'])->firstOrFail();
        $this->assertCount(6, $result->resultScores);
        $this->assertEqualsCanonicalizing(['R', 'I', 'A', 'S', 'E', 'C'], $result->resultScores->pluck('riasec_code')->all());
        $this->assertCount(5, $result->resultRecommendations);
        $this->assertSame('1.2', $result->scoring_version);
        $this->assertSame('1.0', $result->catalog_version);
        $this->assertDatabaseHas('assessment_sessions', [
            'id' => $session->id,
            'status' => 'completed',
        ]);
    }

    public function test_repeating_completion_returns_the_existing_result(): void
    {
        [$student, $session] = $this->makeCompleteSession(18);

        $first = $this->actingAs($student)->postJson(route('assessment.sessions.complete', $session));
        $second = $this->actingAs($student)->postJson(route('assessment.sessions.complete', $session));

        $first->assertOk();
        $second->assertOk();
        $this->assertSame($first->json('data.result_id'), $second->json('data.result_id'));
        $this->assertSame(1, Result::count());
    }

    public function test_fewer_than_fifteen_valid_answers_are_rejected_without_a_result(): void
    {
        [$student, $session] = $this->makeCompleteSession(14);

        $this->actingAs($student)
            ->postJson(route('assessment.sessions.complete', $session))
            ->assertStatus(409)
            ->assertJsonPath('code', 'SESSION_NOT_READY');

        $this->assertSame(0, Result::count());
        $this->assertDatabaseHas('assessment_sessions', ['id' => $session->id, 'status' => 'in_progress']);
    }

    public function test_missing_question_is_rejected_without_a_result(): void
    {
        [$student, $session] = $this->makeCompleteSession(18);
        $session->answers()->firstOrFail()->delete();

        $this->actingAs($student)
            ->postJson(route('assessment.sessions.complete', $session))
            ->assertStatus(409)
            ->assertJsonPath('code', 'SESSION_NOT_READY');

        $this->assertSame(0, Result::count());
    }

    public function test_failure_during_recommendation_rolls_back_everything(): void
    {
        [$student, $session] = $this->makeCompleteSession(18);
        $eventName = 'eloquent.creating: '.ResultRecommendation::class;
        Event::listen($eventName, static function (): never {
            throw new RuntimeException('forced failure after result and scores are created');
        });

        try {
            $this->actingAs($student)
                ->postJson(route('assessment.sessions.complete', $session))
                ->assertStatus(500);
        } finally {
            Event::forget($eventName);
        }

        $this->assertSame(0, Result::count());
        $this->assertDatabaseCount('result_scores', 0);
        $this->assertDatabaseCount('result_recommendations', 0);
        $this->assertDatabaseHas('assessment_sessions', ['id' => $session->id, 'status' => 'in_progress']);
    }

    public function test_a_stale_session_model_cannot_save_after_completion(): void
    {
        [, $staleSession] = $this->makeCompleteSession(18);
        $answer = $staleSession->answers()->with('question.questionOptions')->firstOrFail();

        AssessmentSession::query()->whereKey($staleSession->id)->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        $this->expectException(SessionCompletedException::class);

        app(AssessmentSessionService::class)->saveAnswer(
            $staleSession,
            $answer->question,
            [
                'primary_option_id' => null,
                'none_selected' => true,
                'unable_to_judge' => false,
                'ratings' => [],
            ]
        );
    }

    /** @return array{User, AssessmentSession} */
    private function makeCompleteSession(int $validAnswers): array
    {
        $student = User::factory()->create(['role' => 'student']);
        $version = AssessmentVersion::create([
            'version_number' => 12,
            'status' => 'active',
            'published_at' => now(),
        ]);
        $session = AssessmentSession::create([
            'user_id' => $student->id,
            'assessment_version_id' => $version->id,
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        $codes = ['R', 'I', 'A', 'S', 'E', 'C'];
        for ($position = 1; $position <= 18; $position++) {
            $question = Question::create([
                'assessment_version_id' => $version->id,
                'position' => $position,
                'scenario' => "الموقف {$position}",
            ]);
            $options = [];
            for ($optionPosition = 1; $optionPosition <= 4; $optionPosition++) {
                $options[] = QuestionOption::create([
                    'question_id' => $question->id,
                    'position' => $optionPosition,
                    'option_text' => "الخيار {$optionPosition}",
                    'riasec_code' => $codes[($position + $optionPosition - 2) % 6],
                ]);
            }

            $isValid = $position <= $validAnswers;
            Answer::create([
                'assessment_session_id' => $session->id,
                'question_id' => $question->id,
                'primary_option_id' => $isValid ? $options[0]->id : null,
                'response_type' => $isValid ? 'option' : 'cannot_judge',
            ]);
        }

        return [$student, $session];
    }
}
