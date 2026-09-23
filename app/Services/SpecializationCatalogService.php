<?php

namespace App\Services;

use RuntimeException;

class SpecializationCatalogService
{
    private const EXPECTED_SPECIALIZATION_COUNT = 10;

    /** @var array<int, array<string, mixed>>|null */
    private ?array $catalog = null;

    public function __construct(
        private readonly ?string $catalogPath = null,
    ) {}

    /** @return array<int, array<string, mixed>> */
    public function all(): array
    {
        if ($this->catalog !== null) {
            return $this->catalog;
        }

        $path = $this->catalogPath ?? resource_path('data/specializations.json');
        $json = @file_get_contents($path);

        if ($json === false) {
            throw new RuntimeException('The specialization catalog could not be read.');
        }

        $catalog = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        if (! is_array($catalog) || count($catalog) !== self::EXPECTED_SPECIALIZATION_COUNT) {
            throw new RuntimeException('The specialization catalog is invalid.');
        }

        $ids = [];
        foreach ($catalog as $specialization) {
            foreach (['id', 'name', 'version', 'riasec_primary', 'riasec_secondary', 'riasec_profile'] as $field) {
                if (! array_key_exists($field, $specialization)) {
                    throw new RuntimeException("The specialization catalog is missing {$field}.");
                }
            }

            if (! is_string($specialization['id']) || $specialization['id'] === ''
                || ! is_string($specialization['name']) || $specialization['name'] === ''
                || ! is_string($specialization['version']) || $specialization['version'] === '') {
                throw new RuntimeException('The specialization catalog contains invalid identity fields.');
            }

            if (isset($ids[$specialization['id']])) {
                throw new RuntimeException('The specialization catalog contains duplicate identifiers.');
            }

            $codes = array_merge([$specialization['riasec_primary']], $specialization['riasec_secondary']);
            if (count($specialization['riasec_secondary']) !== 2
                || count(array_unique($codes)) !== 3
                || array_diff($codes, ScoringService::RIASEC_CODES) !== []) {
                throw new RuntimeException('The specialization catalog contains an invalid RIASEC profile.');
            }

            $profile = $specialization['riasec_profile'];
            if (! is_array($profile)
                || count($profile) !== count(ScoringService::RIASEC_CODES)
                || array_diff(array_keys($profile), ScoringService::RIASEC_CODES) !== []
                || array_diff(ScoringService::RIASEC_CODES, array_keys($profile)) !== []
                || collect($profile)->contains(fn (mixed $value): bool => ! is_numeric($value) || $value < 0 || $value > 1)
                || (float) $profile[$specialization['riasec_primary']] <= 0) {
                throw new RuntimeException('The specialization catalog must contain a valid six-domain RIASEC profile.');
            }

            $ids[$specialization['id']] = true;
        }

        return $this->catalog = array_values($catalog);
    }

    public function version(): string
    {
        $versions = array_values(array_unique(array_column($this->all(), 'version')));

        if (count($versions) !== 1) {
            throw new RuntimeException('The specialization catalog must have one consistent version.');
        }

        return (string) $versions[0];
    }
}
