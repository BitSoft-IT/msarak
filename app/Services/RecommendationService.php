<?php

namespace App\Services;

class RecommendationService
{
    /**
     * The current catalog provides an ordered three-code profile rather than
     * six numeric values. These weights preserve that approved ordering while
     * producing the six-dimensional vector required by cosine similarity.
     */
    private const PRIMARY_PROFILE_WEIGHT = 1.0;

    private const SECONDARY_PROFILE_WEIGHT = 0.5;

    public function __construct(
        protected SpecializationCatalogService $catalogService
    ) {}

    /**
     * @param  array<string, float|int>  $scores
     * @return array<int, array{specialization_key: string, name: string, similarity_score: float, rationale: string}>
     */
    public function recommend(array $scores, int $limit = 5): array
    {
        $limit = max(3, min(5, $limit));
        $recommendations = [];

        foreach ($this->catalogService->all() as $specialization) {
            $profile = array_fill_keys(ScoringService::RIASEC_CODES, 0.0);
            $profile[$specialization['riasec_primary']] = self::PRIMARY_PROFILE_WEIGHT;

            foreach ($specialization['riasec_secondary'] as $code) {
                $profile[$code] = self::SECONDARY_PROFILE_WEIGHT;
            }

            $similarity = $this->cosineSimilarity($scores, $profile);
            $secondary = implode(' و', $specialization['riasec_secondary']);

            $recommendations[] = [
                'specialization_key' => $specialization['id'],
                'name' => $specialization['name'],
                'similarity_score' => round($similarity, 4),
                'rationale' => "يتقارب هذا التخصص مع مجال {$specialization['riasec_primary']} أساسًا، ومع المجالين {$secondary} بدرجة مساندة.",
            ];
        }

        usort($recommendations, function (array $left, array $right): int {
            return ($right['similarity_score'] <=> $left['similarity_score'])
                ?: strcmp($left['specialization_key'], $right['specialization_key']);
        });

        return array_slice($recommendations, 0, $limit);
    }

    /** @param array<string, float|int> $left @param array<string, float|int> $right */
    private function cosineSimilarity(array $left, array $right): float
    {
        $dot = 0.0;
        $leftMagnitude = 0.0;
        $rightMagnitude = 0.0;

        foreach (ScoringService::RIASEC_CODES as $code) {
            $leftValue = (float) ($left[$code] ?? 0);
            $rightValue = (float) ($right[$code] ?? 0);
            $dot += $leftValue * $rightValue;
            $leftMagnitude += $leftValue ** 2;
            $rightMagnitude += $rightValue ** 2;
        }

        if ($leftMagnitude === 0.0 || $rightMagnitude === 0.0) {
            return 0.0;
        }

        return $dot / (sqrt($leftMagnitude) * sqrt($rightMagnitude));
    }
}
