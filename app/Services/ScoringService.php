<?php

namespace App\Services;

use App\Models\Answer;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class ScoringService
{
    public const VERSION = '1.2';

    public const LAMBDA = 2.0;

    public const MAX_RATING_WEIGHT = 0.30;

    public const RIASEC_CODES = ['R', 'I', 'A', 'S', 'E', 'C'];

    /**
     * Calculate the six Assessment v1.2 RIASEC scores.
     *
     * A missing rating row is deliberately different from rating=0: only an
     * existing row increases M_d and rating coverage.
     *
     * @param  iterable<Answer>  $answers
     * @return array<string, float>
     */
    public function calculate(iterable $answers): array
    {
        $answers = $answers instanceof Collection ? $answers : collect($answers);
        $terms = [];

        foreach (self::RIASEC_CODES as $code) {
            $terms[$code] = ['n' => 0, 'k' => 0, 'm' => 0, 'rating_sum' => 0];
        }

        foreach ($answers as $answer) {
            if (! $answer instanceof Answer) {
                throw new InvalidArgumentException('ScoringService accepts Answer models only.');
            }

            if ($answer->response_type === 'cannot_judge') {
                continue;
            }

            $answer->loadMissing(['question.questionOptions', 'primaryOption', 'optionRatings.questionOption']);

            $questionCodes = $answer->question->questionOptions
                ->pluck('riasec_code')
                ->unique();

            foreach ($questionCodes as $code) {
                $this->assertCode($code);
                $terms[$code]['n']++;
            }

            if ($answer->response_type === 'option') {
                if (! $answer->primaryOption) {
                    throw new InvalidArgumentException('An option answer must have a primary option.');
                }

                $this->assertCode($answer->primaryOption->riasec_code);
                $terms[$answer->primaryOption->riasec_code]['k']++;
            }

            foreach ($answer->optionRatings as $rating) {
                if (! $rating->questionOption) {
                    throw new InvalidArgumentException('A rating must reference a question option.');
                }

                if ($rating->questionOption->question_id !== $answer->question_id) {
                    throw new InvalidArgumentException('A rating option must belong to the answered question.');
                }

                $code = $rating->questionOption->riasec_code;
                $this->assertCode($code);
                $terms[$code]['m']++;
                $terms[$code]['rating_sum'] += (int) $rating->rating;
            }
        }

        $scores = [];

        foreach ($terms as $code => $term) {
            if ($term['n'] === 0) {
                $scores[$code] = 0.0;

                continue;
            }

            $primary = $term['k'] / $term['n'];
            $ratingRaw = $term['rating_sum'] / ($term['m'] + self::LAMBDA);
            $ratingNormalized = ($ratingRaw + 2) / 4;
            $coverage = min(1.0, $term['m'] / $term['n']);
            $ratingWeight = self::MAX_RATING_WEIGHT * $coverage;
            $score = 100 * (((1 - $ratingWeight) * $primary) + ($ratingWeight * $ratingNormalized));

            $scores[$code] = round(max(0, min(100, $score)), 2);
        }

        return $scores;
    }

    private function assertCode(string $code): void
    {
        if (! in_array($code, self::RIASEC_CODES, true)) {
            throw new InvalidArgumentException("Unsupported RIASEC code: {$code}");
        }
    }
}
