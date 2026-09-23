<?php

namespace Tests\Unit;

use Tests\TestCase;

class SpecializationsDataTest extends TestCase
{
    /**
     * الحقول الإلزامية التي يجب أن تتوفر في كل عنصر
     * داخل resources/data/specializations.json
     */
    protected array $requiredStringFields = [
        'id',
        'name',
        'description',
        'study_nature',
        'riasec_primary',
    ];

    protected array $requiredArrayFields = [
        'sources',
        'riasec_secondary',
        'riasec_profile',
        'key_activities',
        'required_skills',
        'career_paths',
    ];

    protected function specializations(): array
    {
        $path = base_path('resources/data/specializations.json');

        $this->assertFileExists(
            $path,
            'ملف specializations.json غير موجود في المسار المتوقع.'
        );

        $content = file_get_contents($path);
        $data = json_decode($content, true);

        $this->assertSame(
            JSON_ERROR_NONE,
            json_last_error(),
            'ملف specializations.json يحتوي على JSON غير صالح: ' . json_last_error_msg()
        );

        $this->assertIsArray($data, 'محتوى specializations.json يجب أن يكون مصفوفة.');

        return $data;
    }

    /** @test */
    public function the_file_is_not_empty(): void
    {
        $data = $this->specializations();

        $this->assertNotEmpty($data, 'ملف specializations.json فارغ.');
    }

    /** @test */
    public function every_specialization_has_all_required_string_fields(): void
    {
        $data = $this->specializations();

        foreach ($data as $index => $item) {
            $label = "index {$index} (id: " . ($item['id'] ?? 'unknown') . ')';

            foreach ($this->requiredStringFields as $field) {
                $this->assertArrayHasKey(
                    $field,
                    $item,
                    "التخصص في {$label} ينقصه الحقل الإلزامي: {$field}"
                );

                $this->assertNotSame(
                    '',
                    trim((string) $item[$field]),
                    "التخصص في {$label} يحتوي على قيمة فارغة في الحقل: {$field}"
                );
            }
        }
    }

    /** @test */
    public function every_specialization_has_all_required_array_fields(): void
    {
        $data = $this->specializations();

        foreach ($data as $index => $item) {
            $label = "index {$index} (id: " . ($item['id'] ?? 'unknown') . ')';

            foreach ($this->requiredArrayFields as $field) {
                $this->assertArrayHasKey(
                    $field,
                    $item,
                    "التخصص في {$label} ينقصه الحقل الإلزامي: {$field}"
                );

                $this->assertIsArray(
                    $item[$field],
                    "الحقل {$field} في {$label} يجب أن يكون مصفوفة."
                );

                $this->assertNotEmpty(
                    $item[$field],
                    "الحقل {$field} في {$label} فارغ."
                );
            }
        }
    }

    /** @test */
    public function all_specialization_ids_are_unique(): void
    {
        $data = $this->specializations();

        $ids = array_column($data, 'id');

        $this->assertCount(
            count($ids),
            array_unique($ids),
            'يوجد تكرار في قيم id بين التخصصات.'
        );
    }

    /** @test */
    public function riasec_profile_contains_all_six_dimensions(): void
    {
        $data = $this->specializations();
        $expectedKeys = ['R', 'I', 'A', 'S', 'E', 'C'];

        foreach ($data as $index => $item) {
            $label = "index {$index} (id: " . ($item['id'] ?? 'unknown') . ')';

            foreach ($expectedKeys as $key) {
                $this->assertArrayHasKey(
                    $key,
                    $item['riasec_profile'] ?? [],
                    "riasec_profile في {$label} ينقصه البعد: {$key}"
                );
            }
        }
    }
}