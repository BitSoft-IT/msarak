<?php

namespace Tests\Unit;

use App\Models\Answer;
use App\Models\AnswerOptionRating;
use App\Models\AssessmentSession;
use App\Models\AssessmentVersion;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\User;
use App\Services\ScoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScoringServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_applies_v12_formula_and_counts_an_explicit_zero_rating(): void
    {
        [$answer, $options] = $this->makeAnswer('option', 'I');

        AnswerOptionRating::create([
            'answer_id' => $answer->id,
            'question_option_id' => $options['I']->id,
            'rating' => 0,
        ]);
        AnswerOptionRating::create([
            'answer_id' => $answer->id,
            'question_option_id' => $options['R']->id,
            'rating' => -2,
        ]);

        $scores = app(ScoringService::class)->calculate([$answer]);

        $this->assertSame(['R', 'I', 'A', 'S', 'E', 'C'], array_keys($scores));
        $this->assertSame(10.0, $scores['R']);
        $this->assertSame(85.0, $scores['I']);
        $this->assertSame(0.0, $scores['A']);
        $this->assertSame(0.0, $scores['S']);
    }

    public function test_a_missing_rating_is_different_from_an_explicit_zero(): void
    {
        [$unrated] = $this->makeAnswer('option', 'I');
        [$neutral, $neutralOptions] = $this->makeAnswer('option', 'I');

        AnswerOptionRating::create([
            'answer_id' => $neutral->id,
            'question_option_id' => $neutralOptions['I']->id,
            'rating' => 0,
        ]);

        $service = app(ScoringService::class);

        $this->assertSame(100.0, $service->calculate([$unrated])['I']);
        $this->assertSame(85.0, $service->calculate([$neutral])['I']);
    }

    public function test_none_is_valid_without_primary_and_cannot_judge_is_excluded(): void
    {
        [$none] = $this->makeAnswer('none');
        [$cannotJudge] = $this->makeAnswer('cannot_judge');

        $scores = app(ScoringService::class)->calculate([$none, $cannotJudge]);

        $this->assertSame(0.0, $scores['R']);
        $this->assertSame(0.0, $scores['I']);
        $this->assertSame(0.0, $scores['A']);
        $this->assertSame(0.0, $scores['S']);
    }

    public function test_it_matches_the_manually_calculated_example_in_the_v12_specification(): void
    {
        $answers = collect();
        $ratings = [2, 2, 1, 1, 0];

        for ($index = 0; $index < 10; $index++) {
            [$answer, $options] = $this->makeAnswer('option', $index < 4 ? 'I' : 'R');
            $answers->push($answer);

            if (array_key_exists($index, $ratings)) {
                AnswerOptionRating::create([
                    'answer_id' => $answer->id,
                    'question_option_id' => $options['I']->id,
                    'rating' => $ratings[$index],
                ]);
            }
        }

        $scores = app(ScoringService::class)->calculate($answers);

        // N_I=10, K_I=4, M_I=5, sum=6, lambda=2, W_I=0.15.
        $this->assertSame(44.71, $scores['I']);
    }

    /** @return array{Answer, array<string, QuestionOption>} */
    private function makeAnswer(string $responseType, ?string $primaryCode = null): array
    {
        $user = User::factory()->create(['role' => 'student']);
        $version = AssessmentVersion::create([
            'version_number' => AssessmentVersion::max('version_number') + 1,
            'status' => 'draft',
        ]);
        $question = Question::create([
            'assessment_version_id' => $version->id,
            'position' => 1,
            'scenario' => 'موقف اختباري',
        ]);
        $options = [];
        foreach (['R', 'I', 'A', 'S'] as $position => $code) {
            $options[$code] = QuestionOption::create([
                'question_id' => $question->id,
                'position' => $position + 1,
                'option_text' => "خيار {$code}",
                'riasec_code' => $code,
            ]);
        }
        $session = AssessmentSession::create([
            'user_id' => $user->id,
            'assessment_version_id' => $version->id,
            'status' => 'in_progress',
            'started_at' => now(),
        ]);
        $answer = Answer::create([
            'assessment_session_id' => $session->id,
            'question_id' => $question->id,
            'primary_option_id' => $primaryCode ? $options[$primaryCode]->id : null,
            'response_type' => $responseType,
        ]);

        return [$answer, $options];
    }
}
