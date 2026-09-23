<?php

namespace Tests\Unit;

use App\Services\RecommendationService;
use Tests\TestCase;

class RecommendationServiceTest extends TestCase
{
    public function test_it_returns_ranked_backend_recommendations_from_the_catalog(): void
    {
        $recommendations = app(RecommendationService::class)->recommend([
            'R' => 50,
            'I' => 100,
            'A' => 0,
            'S' => 0,
            'E' => 0,
            'C' => 50,
        ]);

        $this->assertCount(5, $recommendations);
        $this->assertSame('computer_science', $recommendations[0]['specialization_key']);
        $this->assertSame(1.0, $recommendations[0]['similarity_score']);
        $this->assertNotEmpty($recommendations[0]['rationale']);

        $similarities = array_column($recommendations, 'similarity_score');
        $sorted = $similarities;
        rsort($sorted);
        $this->assertSame($sorted, $similarities);
    }
}
