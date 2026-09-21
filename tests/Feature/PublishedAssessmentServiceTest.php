<?php

namespace Tests\Feature;

use App\Exceptions\AssessmentUnavailableException;
use App\Models\AssessmentVersion;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Services\PublishedAssessmentService;
use Database\Seeders\AssessmentQuestionBankSeeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use Tests\TestCase;

/**
 * Verifies B-02: resolving the published assessment version and reading the
 * question bank as a student-safe payload.
 *
 * The service is read-only, never leaks RIASEC codes or scoring data, and
 * reports zero/multiple published versions as a clear failure rather than
 * silently guessing.
 */
class PublishedAssessmentServiceTest extends TestCase
{
    use RefreshDatabase;

    private const RIASEC_CODES = ['R', 'I', 'A', 'S', 'E', 'C'];

    private const LEGACY_FIELDS = [
        'closest_weight',
        'least_weight',
        'closest_option_id',
        'least_option_id',
        'is_skipped',
    ];

    private PublishedAssessmentService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new PublishedAssessmentService();
    }

    // ---------------------------------------------------------------
    // Test helpers — build minimal versions/questions/options directly.
    // No factories are introduced for B-02.
    // ---------------------------------------------------------------

    private function createVersion(int $versionNumber, string $status, bool $published = true): AssessmentVersion
    {
        return AssessmentVersion::create([
            'version_number' => $versionNumber,
            'status' => $status,
            'published_at' => $published ? now() : null,
        ]);
    }

    private function createQuestion(AssessmentVersion $version, int $position, int $optionCount = 4): Question
    {
        $question = Question::create([
            'assessment_version_id' => $version->id,
            'position' => $position,
            'scenario' => "موقف تجريبي رقم {$position} للإصدار {$version->version_number}",
        ]);

        for ($i = 1; $i <= $optionCount; $i++) {
            QuestionOption::create([
                'question_id' => $question->id,
                'position' => $i,
                'option_text' => "خيار {$i} للسؤال {$position}",
                'riasec_code' => self::RIASEC_CODES[($i - 1) % count(self::RIASEC_CODES)],
            ]);
        }

        return $question;
    }

    /**
     * Recursively collect every array key at any depth.
     *
     * @return string[]
     */
    private function recursiveKeys(array $data): array
    {
        $keys = [];

        foreach ($data as $key => $value) {
            $keys[] = (string) $key;

            if (is_array($value)) {
                $keys = array_merge($keys, $this->recursiveKeys($value));
            }
        }

        return $keys;
    }

    // ===============================================================
    // 1. Resolving the published version
    // ===============================================================

    public function test_returns_the_single_active_published_version(): void
    {
        $draft = $this->createVersion(1, 'draft');
        $retired = $this->createVersion(2, 'retired');
        $active = $this->createVersion(3, 'active');

        $resolved = $this->service->getPublishedVersion();

        $this->assertSame($active->id, $resolved->id, 'The single active published version must be resolved.');
        $this->assertSame('active', $resolved->status);
        $this->assertNotNull($resolved->published_at);
        $this->assertNotSame($draft->id, $resolved->id);
        $this->assertNotSame($retired->id, $resolved->id);
    }

    public function test_draft_status_is_never_selected(): void
    {
        $this->createVersion(1, 'draft', published: true);

        try {
            $this->service->getPublishedVersion();
            $this->fail('A draft version must never be treated as published.');
        } catch (AssessmentUnavailableException $e) {
            $this->assertSame(
                AssessmentUnavailableException::REASON_NO_PUBLISHED_VERSION,
                $e->getReason()
            );
        }
    }

    public function test_retired_status_is_never_selected(): void
    {
        $this->createVersion(1, 'retired', published: true);

        try {
            $this->service->getPublishedVersion();
            $this->fail('A retired version must never be treated as published.');
        } catch (AssessmentUnavailableException $e) {
            $this->assertSame(
                AssessmentUnavailableException::REASON_NO_PUBLISHED_VERSION,
                $e->getReason()
            );
        }
    }

    public function test_active_version_without_published_at_is_never_selected(): void
    {
        $this->createVersion(1, 'active', published: false);

        try {
            $this->service->getPublishedVersion();
            $this->fail('An active version with published_at = NULL must not be used.');
        } catch (AssessmentUnavailableException $e) {
            $this->assertSame(
                AssessmentUnavailableException::REASON_NO_PUBLISHED_VERSION,
                $e->getReason()
            );
        }
    }

    public function test_no_versions_at_all_fails_clearly(): void
    {
        try {
            $this->service->getPublishedVersion();
            $this->fail('With no published version, the service must fail clearly.');
        } catch (AssessmentUnavailableException $e) {
            $this->assertSame(
                AssessmentUnavailableException::REASON_NO_PUBLISHED_VERSION,
                $e->getReason()
            );
        }
    }

    public function test_multiple_active_published_versions_fail_loudly(): void
    {
        $first = $this->createVersion(1, 'active');
        $second = $this->createVersion(2, 'active');

        try {
            $this->service->getPublishedVersion();
            $this->fail('Multiple published versions are a data-integrity fault and must not be resolved silently.');
        } catch (AssessmentUnavailableException $e) {
            $this->assertSame(
                AssessmentUnavailableException::REASON_MULTIPLE_PUBLISHED_VERSIONS,
                $e->getReason()
            );
        }

        // Neither row was mutated while detecting the conflict.
        $this->assertSame('active', AssessmentVersion::find($first->id)->status);
        $this->assertSame('active', AssessmentVersion::find($second->id)->status);
    }

    public function test_unavailable_exception_renders_a_safe_envelope(): void
    {
        $exception = AssessmentUnavailableException::noPublishedVersion();

        $response = $exception->render(Request::create('/assessment/sessions'));
        $payload = $response->getData(assoc: true);

        $this->assertSame(409, $response->getStatusCode());
        $this->assertSame(AssessmentUnavailableException::ERROR_CODE, $payload['code']);
        $this->assertSame($exception->getMessage(), $payload['message']);
        $this->assertArrayNotHasKey('errors', $payload);

        $rendered = json_encode($payload);
        $this->assertStringNotContainsString('SQL', $rendered);
        $this->assertStringNotContainsString('trace', $rendered);
        $this->assertStringNotContainsString($exception->getReason(), $rendered);
    }

    // ===============================================================
    // 1b. The student payload can never bypass published resolution
    // ===============================================================

    public function test_get_student_payload_uses_the_published_version_automatically(): void
    {
        $draft = $this->createVersion(1, 'draft');
        $retired = $this->createVersion(2, 'retired');
        $active = $this->createVersion(3, 'active');

        $this->createQuestion($active, 1);

        $payload = $this->service->getStudentPayload();

        $this->assertSame($active->id, $payload['assessment_version_id']);
        $this->assertSame($active->version_number, $payload['version_number']);
        $this->assertNotSame($draft->id, $payload['assessment_version_id']);
        $this->assertNotSame($retired->id, $payload['assessment_version_id']);
    }

    public function test_get_student_payload_refuses_a_draft_only_bank(): void
    {
        $draft = $this->createVersion(1, 'draft');
        $this->createQuestion($draft, 1);

        try {
            $this->service->getStudentPayload();
            $this->fail('A draft version must never become the source of a student payload.');
        } catch (AssessmentUnavailableException $e) {
            $this->assertSame(
                AssessmentUnavailableException::REASON_NO_PUBLISHED_VERSION,
                $e->getReason()
            );
        }
    }

    public function test_get_student_payload_refuses_a_retired_only_bank(): void
    {
        $retired = $this->createVersion(1, 'retired');
        $this->createQuestion($retired, 1);

        try {
            $this->service->getStudentPayload();
            $this->fail('A retired version must never become the source of a student payload.');
        } catch (AssessmentUnavailableException $e) {
            $this->assertSame(
                AssessmentUnavailableException::REASON_NO_PUBLISHED_VERSION,
                $e->getReason()
            );
        }
    }

    public function test_get_student_payload_refuses_an_unpublished_active_bank(): void
    {
        $unpublished = $this->createVersion(1, 'active', published: false);
        $this->createQuestion($unpublished, 1);

        try {
            $this->service->getStudentPayload();
            $this->fail('An active version with published_at = NULL must never become the source of a student payload.');
        } catch (AssessmentUnavailableException $e) {
            $this->assertSame(
                AssessmentUnavailableException::REASON_NO_PUBLISHED_VERSION,
                $e->getReason()
            );
        }
    }

    public function test_get_student_payload_fails_when_no_version_is_published(): void
    {
        try {
            $this->service->getStudentPayload();
            $this->fail('With no published version, no student payload may be produced.');
        } catch (AssessmentUnavailableException $e) {
            $this->assertSame(
                AssessmentUnavailableException::REASON_NO_PUBLISHED_VERSION,
                $e->getReason()
            );
        }
    }

    public function test_get_student_payload_fails_when_multiple_versions_are_published(): void
    {
        $this->createVersion(1, 'active');
        $this->createVersion(2, 'active');

        try {
            $this->service->getStudentPayload();
            $this->fail('Ambiguous published versions must block the student payload.');
        } catch (AssessmentUnavailableException $e) {
            $this->assertSame(
                AssessmentUnavailableException::REASON_MULTIPLE_PUBLISHED_VERSIONS,
                $e->getReason()
            );
        }
    }

    public function test_no_public_method_accepts_an_arbitrary_version(): void
    {
        $reflection = new ReflectionClass(PublishedAssessmentService::class);

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            foreach ($method->getParameters() as $parameter) {
                $type = $parameter->getType();
                $typeName = $type instanceof ReflectionNamedType ? $type->getName() : null;

                $this->assertNotSame(
                    AssessmentVersion::class,
                    $typeName,
                    "Public method {$method->getName()}() must not accept an arbitrary AssessmentVersion."
                );
            }
        }

        $this->assertTrue(
            $reflection->hasMethod('getStudentPayload'),
            'The public payload entry point must remain getStudentPayload().'
        );
        $this->assertTrue(
            $reflection->getMethod('buildStudentPayloadForVersion')->isPrivate(),
            'The version-specific builder must stay private.'
        );
    }

    // ===============================================================
    // 2. Questions and options belong to the resolved version
    // ===============================================================

    public function test_questions_belong_only_to_the_selected_version(): void
    {
        $published = $this->createVersion(1, 'active');
        $other = $this->createVersion(2, 'draft');

        $this->createQuestion($published, 1);
        $this->createQuestion($published, 2);
        $this->createQuestion($other, 1);
        $this->createQuestion($other, 2);

        $payload = $this->service->getStudentPayload();

        $publishedQuestionIds = Question::where('assessment_version_id', $published->id)->pluck('id')->all();
        $otherQuestionIds = Question::where('assessment_version_id', $other->id)->pluck('id')->all();

        $payloadQuestionIds = array_column($payload['questions'], 'question_id');

        $this->assertCount(2, $payload['questions']);
        $this->assertSame($publishedQuestionIds, $payloadQuestionIds);
        $this->assertEmpty(array_intersect($payloadQuestionIds, $otherQuestionIds));
    }

    public function test_questions_from_another_version_do_not_appear(): void
    {
        $published = $this->createVersion(1, 'active');
        $other = $this->createVersion(2, 'retired', published: true);

        $publishedQuestion = $this->createQuestion($published, 1);
        $otherQuestion = $this->createQuestion($other, 1);

        $payload = $this->service->getStudentPayload();

        $scenarios = array_column($payload['questions'], 'scenario');

        $this->assertContains($publishedQuestion->scenario, $scenarios);
        $this->assertNotContains($otherQuestion->scenario, $scenarios);
    }

    public function test_questions_are_ordered_by_position_ascending(): void
    {
        $version = $this->createVersion(1, 'active');

        // Insert out of order; the service must still return position ASC.
        $this->createQuestion($version, 3);
        $this->createQuestion($version, 1);
        $this->createQuestion($version, 2);

        $payload = $this->service->getStudentPayload();

        $positions = array_column($payload['questions'], 'position');

        $this->assertSame([1, 2, 3], $positions, 'Questions must always be ordered by position ASC.');
    }

    public function test_options_belong_only_to_their_question(): void
    {
        $version = $this->createVersion(1, 'active');
        $first = $this->createQuestion($version, 1);
        $second = $this->createQuestion($version, 2);

        $payload = $this->service->getStudentPayload();

        $byQuestion = [];
        foreach ($payload['questions'] as $questionPayload) {
            $byQuestion[$questionPayload['question_id']] = array_column($questionPayload['options'], 'option_id');
        }

        $firstOptionIds = QuestionOption::where('question_id', $first->id)->pluck('id')->all();
        $secondOptionIds = QuestionOption::where('question_id', $second->id)->pluck('id')->all();

        $this->assertSame($firstOptionIds, $byQuestion[$first->id]);
        $this->assertSame($secondOptionIds, $byQuestion[$second->id]);
        $this->assertEmpty(array_intersect($firstOptionIds, $secondOptionIds));
    }

    public function test_options_from_another_question_do_not_appear(): void
    {
        $version = $this->createVersion(1, 'active');
        $first = $this->createQuestion($version, 1);
        $second = $this->createQuestion($version, 2);

        $payload = $this->service->getStudentPayload();

        $firstPayload = collect($payload['questions'])->firstWhere('question_id', $first->id);
        $strayOptionId = QuestionOption::where('question_id', $second->id)->value('id');

        $this->assertNotContains($strayOptionId, array_column($firstPayload['options'], 'option_id'));
    }

    public function test_payload_top_level_shape_is_minimal(): void
    {
        $version = $this->createVersion(1, 'active');
        $this->createQuestion($version, 1);

        $payload = $this->service->getStudentPayload();

        $this->assertSame(
            ['assessment_version_id', 'version_number', 'questions'],
            array_keys($payload)
        );
        $this->assertSame($version->id, $payload['assessment_version_id']);
        $this->assertSame($version->version_number, $payload['version_number']);
    }

    // ===============================================================
    // 3. RIASEC and legacy scoring data must never leak
    // ===============================================================

    public function test_student_payload_never_exposes_riasec_code(): void
    {
        $version = $this->createVersion(1, 'active');
        $this->createQuestion($version, 1);
        $this->createQuestion($version, 2);

        $payload = $this->service->getStudentPayload();

        $this->assertNotContains('riasec_code', $this->recursiveKeys($payload));

        // No code value leaks as an option text either.
        $rendered = json_encode($payload, JSON_UNESCAPED_UNICODE);
        foreach (self::RIASEC_CODES as $code) {
            $this->assertStringNotContainsString('"'.$code.'"', $rendered);
        }

        // And it is genuinely present in the database, proving the filter works.
        $this->assertGreaterThan(0, QuestionOption::count());
        $this->assertSame(8, QuestionOption::whereIn('riasec_code', self::RIASEC_CODES)->count());
    }

    public function test_student_payload_never_exposes_legacy_scoring_fields(): void
    {
        $version = $this->createVersion(1, 'active');
        $this->createQuestion($version, 1);

        $payload = $this->service->getStudentPayload();

        $keys = $this->recursiveKeys($payload);

        foreach (self::LEGACY_FIELDS as $field) {
            $this->assertNotContains($field, $keys, "B-02 must not reintroduce the legacy field {$field}.");
        }
    }

    public function test_option_payload_contains_only_safe_fields(): void
    {
        $version = $this->createVersion(1, 'active');
        $this->createQuestion($version, 1);

        $payload = $this->service->getStudentPayload();

        foreach ($payload['questions'] as $questionPayload) {
            foreach ($questionPayload['options'] as $optionPayload) {
                $this->assertSame(
                    ['option_id', 'option_text'],
                    array_keys($optionPayload),
                    'An option must expose only option_id and option_text.'
                );
            }
        }
    }

    // ===============================================================
    // 4. Shuffle is display-only
    // ===============================================================

    public function test_shuffle_preserves_every_option_id(): void
    {
        $version = $this->createVersion(1, 'active');
        $question = $this->createQuestion($version, 1);

        $original = $this->service->getStudentPayload();
        $shuffled = $this->service->getStudentPayload(shuffleOptions: true);

        $originalIds = array_column($original['questions'][0]['options'], 'option_id');
        $shuffledIds = array_column($shuffled['questions'][0]['options'], 'option_id');

        $this->assertCount(4, $shuffledIds, 'Shuffle must not drop or add options.');

        // Same id set, regardless of the order shuffle happened to pick.
        $this->assertEqualsCanonicalizing($originalIds, $shuffledIds, 'Shuffle must preserve the exact option id set.');
        $this->assertEqualsCanonicalizing(
            QuestionOption::where('question_id', $question->id)->pluck('id')->all(),
            $shuffledIds
        );

        // Option texts stay bound to their ids.
        $textsById = [];
        foreach ($original['questions'][0]['options'] as $option) {
            $textsById[$option['option_id']] = $option['option_text'];
        }
        foreach ($shuffled['questions'][0]['options'] as $option) {
            $this->assertSame($textsById[$option['option_id']], $option['option_text']);
        }
    }

    public function test_shuffle_produces_no_duplicate_ids(): void
    {
        $version = $this->createVersion(1, 'active');
        $this->createQuestion($version, 1);

        $shuffled = $this->service->getStudentPayload(shuffleOptions: true);
        $ids = array_column($shuffled['questions'][0]['options'], 'option_id');

        $this->assertCount(4, $ids);
        $this->assertSame($ids, array_values(array_unique($ids)), 'Shuffle must never duplicate an option id.');
    }

    public function test_shuffle_keeps_each_option_attached_to_its_question(): void
    {
        $version = $this->createVersion(1, 'active');
        $first = $this->createQuestion($version, 1);
        $second = $this->createQuestion($version, 2);

        $shuffled = $this->service->getStudentPayload(shuffleOptions: true);

        foreach ($shuffled['questions'] as $questionPayload) {
            $expected = QuestionOption::where('question_id', $questionPayload['question_id'])
                ->pluck('id')
                ->all();

            $actual = array_column($questionPayload['options'], 'option_id');

            $this->assertEqualsCanonicalizing($expected, $actual);
        }

        // Options never migrate between questions.
        $firstIds = array_column(
            collect($shuffled['questions'])->firstWhere('question_id', $first->id)['options'],
            'option_id'
        );
        $secondIds = array_column(
            collect($shuffled['questions'])->firstWhere('question_id', $second->id)['options'],
            'option_id'
        );

        $this->assertEmpty(array_intersect($firstIds, $secondIds));
    }

    public function test_shuffle_never_modifies_stored_positions(): void
    {
        $version = $this->createVersion(1, 'active');
        $question = $this->createQuestion($version, 1);

        $this->service->getStudentPayload(shuffleOptions: true);
        $this->service->getStudentPayload(shuffleOptions: true);

        $storedPositions = QuestionOption::where('question_id', $question->id)
            ->orderBy('position')
            ->pluck('position')
            ->all();

        $this->assertSame([1, 2, 3, 4], $storedPositions, 'Shuffle is display-only and must never write to the DB.');
        $this->assertSame(4, QuestionOption::where('question_id', $question->id)->count());
    }

    /**
     * Read the stored updated_at as a plain string, so that two separate
     * reads compare by value instead of by Carbon object identity.
     */
    private function storedUpdatedAt(string $table, int $id): ?string
    {
        $value = DB::table($table)->where('id', $id)->value('updated_at');

        return $value === null ? null : (string) $value;
    }

    // ===============================================================
    // 5. Read-only guarantee
    // ===============================================================

    public function test_reading_does_not_modify_the_database(): void
    {
        $version = $this->createVersion(1, 'active');
        $this->createQuestion($version, 1);
        $this->createQuestion($version, 2);

        $versionUpdatedAt = $this->storedUpdatedAt('assessment_versions', $version->id);
        $questionUpdatedAt = Question::where('assessment_version_id', $version->id)
            ->orderBy('id')
            ->pluck('updated_at')
            ->map(fn ($value): string => (string) $value)
            ->all();

        $versionCount = AssessmentVersion::count();
        $questionCount = Question::count();
        $optionCount = QuestionOption::count();

        $this->service->getStudentPayload(shuffleOptions: true);

        $this->assertSame($versionCount, AssessmentVersion::count(), 'No assessment version row may be added or removed.');
        $this->assertSame($questionCount, Question::count(), 'No question row may be added or removed.');
        $this->assertSame($optionCount, QuestionOption::count(), 'No option row may be added or removed.');

        $this->assertSame(
            $versionUpdatedAt,
            $this->storedUpdatedAt('assessment_versions', $version->id),
            'updated_at must not change on read.'
        );
        $this->assertSame(
            $questionUpdatedAt,
            Question::where('assessment_version_id', $version->id)
                ->orderBy('id')
                ->pluck('updated_at')
                ->map(fn ($value): string => (string) $value)
                ->all(),
            'Question timestamps must not change on read.'
        );
    }

    public function test_reading_uses_a_fixed_number_of_queries(): void
    {
        // A small bank and a large bank must cost the same number of
        // queries: version resolution + questions + eager-loaded options.
        // Anything that grows with the question count is an N+1 regression.
        $smallVersion = $this->createVersion(1, 'active');
        for ($i = 1; $i <= 3; $i++) {
            $this->createQuestion($smallVersion, $i);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->service->getStudentPayload();
        $smallQueries = count(DB::getQueryLog());
        DB::disableQueryLog();

        QuestionOption::query()->delete();
        Question::query()->delete();
        AssessmentVersion::query()->delete();

        $largeVersion = $this->createVersion(2, 'active');
        for ($i = 1; $i <= 18; $i++) {
            $this->createQuestion($largeVersion, $i);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->service->getStudentPayload();
        $largeQueries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertSame(
            $smallQueries,
            count($largeQueries),
            'Reading 3 and 18 questions must issue the same number of queries; an increase means N+1.'
        );

        $this->assertGreaterThan(0, count($largeQueries));

        foreach ($largeQueries as $query) {
            $this->assertStringStartsWith('select', strtolower($query['query']));
        }
    }

    // ===============================================================
    // 6. Integration with the approved Q-01 bank
    // ===============================================================

    public function test_q01_bank_reads_as_eighteen_questions_and_seventy_two_options(): void
    {
        $this->seed(AssessmentQuestionBankSeeder::class);

        $version = $this->service->getPublishedVersion();
        $payload = $this->service->getStudentPayload();

        $this->assertSame(12, $version->version_number, 'Semantic version 1.2 is stored as the integer 12.');
        $this->assertSame($version->id, $payload['assessment_version_id']);
        $this->assertCount(18, $payload['questions'], 'The approved bank has exactly 18 questions.');

        $totalOptions = 0;

        foreach ($payload['questions'] as $index => $questionPayload) {
            $this->assertSame($index + 1, $questionPayload['position']);
            $this->assertCount(4, $questionPayload['options'], 'Every question has exactly 4 options.');
            $totalOptions += count($questionPayload['options']);
        }

        $this->assertSame(72, $totalOptions, 'The approved bank has exactly 72 options.');
        $this->assertSame(18, Question::count());
        $this->assertSame(72, QuestionOption::count());
    }

    public function test_q01_bank_payload_hides_riasec_codes(): void
    {
        $this->seed(AssessmentQuestionBankSeeder::class);

        $payload = $this->service->getStudentPayload();

        $this->assertNotContains('riasec_code', $this->recursiveKeys($payload));

        $rendered = json_encode($payload, JSON_UNESCAPED_UNICODE);
        foreach (self::RIASEC_CODES as $code) {
            $this->assertStringNotContainsString('"'.$code.'"', $rendered);
        }

        // The codes exist in the bank; they simply must not reach the student.
        $this->assertSame(72, QuestionOption::whereIn('riasec_code', self::RIASEC_CODES)->count());
    }

    public function test_q01_bank_remains_unmodified_after_reading(): void
    {
        $this->seed(AssessmentQuestionBankSeeder::class);

        $version = $this->service->getPublishedVersion();

        $versionCount = AssessmentVersion::count();
        $questionCount = Question::count();
        $optionCount = QuestionOption::count();
        $versionUpdatedAt = $this->storedUpdatedAt('assessment_versions', $version->id);

        $this->service->getStudentPayload(shuffleOptions: true);

        $this->assertSame($versionCount, AssessmentVersion::count());
        $this->assertSame($questionCount, Question::count());
        $this->assertSame($optionCount, QuestionOption::count());
        $this->assertSame($versionUpdatedAt, $this->storedUpdatedAt('assessment_versions', $version->id));

        $this->assertSame(
            range(1, 18),
            Question::orderBy('position')->pluck('position')->all()
        );

        Question::orderBy('position')->get()->each(function (Question $question): void {
            $this->assertSame(
                range(1, 4),
                $question->questionOptions()->orderBy('position')->pluck('position')->all()
            );
        });
    }

    public function test_models_expose_riasec_code_internally_without_hiding_it_globally(): void
    {
        $this->seed(AssessmentQuestionBankSeeder::class);

        // Available to backend scoring layers...
        $this->assertContains('riasec_code', (new QuestionOption())->getFillable());
        $this->assertNotContains('riasec_code', (new QuestionOption())->getHidden());

        // ...but absent from the student payload.
        $payload = $this->service->getStudentPayload();

        $this->assertNotContains('riasec_code', $this->recursiveKeys($payload));
    }
}
