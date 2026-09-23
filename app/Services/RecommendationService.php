<?php

namespace App\Services;

class RecommendationService
{
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
            $similarity = $this->cosineSimilarity($scores, $specialization['riasec_profile']);
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
