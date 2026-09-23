<?php

namespace Tests\Unit;

use App\Services\RecommendationService;
use App\Services\SpecializationCatalogService;
use RuntimeException;
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

    public function test_the_catalog_requires_exactly_ten_specializations(): void
    {
        $catalog = json_decode(
            file_get_contents(resource_path('data/specializations.json')),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        array_pop($catalog);

        $path = tempnam(sys_get_temp_dir(), 'specializations-');
        file_put_contents($path, json_encode($catalog, JSON_THROW_ON_ERROR));

        try {
            $this->expectException(RuntimeException::class);
            (new SpecializationCatalogService($path))->all();
        } finally {
            @unlink($path);
        }
    }

    public function test_every_specialization_has_an_explicit_six_domain_profile(): void
    {
        $catalog = app(SpecializationCatalogService::class)->all();

        foreach ($catalog as $specialization) {
            $this->assertSame(['R', 'I', 'A', 'S', 'E', 'C'], array_keys($specialization['riasec_profile']));
        }
    }
}
