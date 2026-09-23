<?php

namespace App\Services;

use RuntimeException;

class SpecializationCatalogService
{
    /** @var array<int, array<string, mixed>>|null */
    private ?array $catalog = null;

    /** @return array<int, array<string, mixed>> */
    public function all(): array
    {
        if ($this->catalog !== null) {
            return $this->catalog;
        }

        $path = resource_path('data/specializations.json');
        $json = @file_get_contents($path);

        if ($json === false) {
            throw new RuntimeException('The specialization catalog could not be read.');
        }

        $catalog = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        if (! is_array($catalog) || count($catalog) < 3) {
            throw new RuntimeException('The specialization catalog is invalid.');
        }

        $ids = [];
        foreach ($catalog as $specialization) {
            foreach (['id', 'name', 'version', 'riasec_primary', 'riasec_secondary'] as $field) {
                if (! array_key_exists($field, $specialization)) {
                    throw new RuntimeException("The specialization catalog is missing {$field}.");
                }
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
