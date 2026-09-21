<?php

namespace Tests\Feature\Database;

use App\Models\AssessmentVersion;
use App\Models\Question;
use App\Models\QuestionOption;
use Database\Seeders\AssessmentQuestionBankSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Q-02 (Phase 2) — Seeder idempotency and referential integrity.
 *
 * AssessmentQuestionBankSeeder is the single source of the approved v1.2 bank.
 * These tests prove that repeated seeding is safe: ids stay stable, no row is
 * duplicated, and no orphan is left behind. Content-level coverage (scenarios,
 * option texts, the balanced RIASEC distribution) already lives in
 * Tests\Feature\AssessmentQuestionBankTest and is deliberately not repeated.
 *
 * Production code is never modified by these tests.
 */
class SeederIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private const VERSION_NUMBER = 12;

    private const QUESTIONS_COUNT = 18;

    private const OPTIONS_PER_QUESTION = 4;

    private const OPTIONS_PER_CODE = 12;

    protected function setUp(): void
    {
        parent::setUp();

        // Every test starts from one seeded run.
        $this->seed(AssessmentQuestionBankSeeder::class);
    }

    public function test_seeding_twice_keeps_stable_row_ids(): void
    {
        // Arrange — snapshot the ids the first run produced.
        $versionId = AssessmentVersion::where('version_number', self::VERSION_NUMBER)->value('id');
        $questionIds = Question::orderBy('id')->pluck('id')->all();
        $optionIds = QuestionOption::orderBy('id')->pluck('id')->all();

        // Act — seed the whole bank a second time.
        $this->seed(AssessmentQuestionBankSeeder::class);

        // Assert — updateOrCreate must update in place, never duplicate.
        $this->assertSame(
            $versionId,
            AssessmentVersion::where('version_number', self::VERSION_NUMBER)->value('id'),
            'The version row id must be stable across seeding runs.'
        );
        $this->assertSame($questionIds, Question::orderBy('id')->pluck('id')->all(), 'Question ids must be stable.');
        $this->assertSame($optionIds, QuestionOption::orderBy('id')->pluck('id')->all(), 'Option ids must be stable.');
    }

    public function test_seeding_twice_does_not_duplicate_the_bank(): void
    {
        // Act
        $this->seed(AssessmentQuestionBankSeeder::class);
        $this->seed(AssessmentQuestionBankSeeder::class);

        // Assert
        $this->assertSame(1, AssessmentVersion::where('version_number', self::VERSION_NUMBER)->count());
        $this->assertSame(self::QUESTIONS_COUNT, Question::count());
        $this->assertSame(self::QUESTIONS_COUNT * self::OPTIONS_PER_QUESTION, QuestionOption::count());
    }

    public function test_seeding_twice_leaves_no_orphaned_rows(): void
    {
        // Act
        $this->seed(AssessmentQuestionBankSeeder::class);

        // Assert — every child still resolves to a live parent.
        $orphanQuestions = DB::table('questions')
            ->leftJoin('assessment_versions', 'questions.assessment_version_id', '=', 'assessment_versions.id')
            ->whereNull('assessment_versions.id')
            ->count();

        $orphanOptions = DB::table('question_options')
            ->leftJoin('questions', 'question_options.question_id', '=', 'questions.id')
            ->whereNull('questions.id')
            ->count();

        $this->assertSame(0, $orphanQuestions, 'No question may point at a missing version.');
        $this->assertSame(0, $orphanOptions, 'No option may point at a missing question.');
    }

    public function test_seeder_validation_passes_on_the_second_run(): void
    {
        // Act — the seeder self-validates inside its transaction; a second run
        // must not trip that guard.
        $this->seed(AssessmentQuestionBankSeeder::class);

        // Assert — the counts prove validation accepted the re-run.
        $this->assertSame(self::QUESTIONS_COUNT, Question::count());
        $this->assertSame(self::QUESTIONS_COUNT * self::OPTIONS_PER_QUESTION, QuestionOption::count());
    }

    public function test_exactly_one_active_version_exists_after_repeated_seeding(): void
    {
        // Act
        $this->seed(AssessmentQuestionBankSeeder::class);
        $this->seed(AssessmentQuestionBankSeeder::class);

        // Assert — spec v1.2: one active version only.
        $this->assertSame(1, AssessmentVersion::where('status', 'active')->count());
        $this->assertSame(1, AssessmentVersion::count());
    }

    public function test_seeded_version_number_represents_release_1_2(): void
    {
        // Act
        $version = AssessmentVersion::first();

        // Assert — the semantic version "1.2" is stored as the integer 12.
        $this->assertSame(self::VERSION_NUMBER, $version->version_number);
    }

    public function test_no_fifth_option_is_persisted_as_a_row(): void
    {
        // Act
        $fifth = QuestionOption::where('option_text', 'like', '%لا يشبهني أي من هذه التصرفات%')->count();

        // Assert — spec v1.2 §4.2: the fifth option is never a question_option.
        $this->assertSame(0, $fifth, 'The fifth option must not be stored as a question_option row.');
        $this->assertSame(
            self::QUESTIONS_COUNT * self::OPTIONS_PER_QUESTION,
            QuestionOption::count(),
            'The option count must stay at exactly four per question.'
        );
    }

    public function test_the_bank_is_readable_as_one_object_graph_after_reseeding(): void
    {
        // Act
        $this->seed(AssessmentQuestionBankSeeder::class);

        $version = AssessmentVersion::with('questions.questionOptions')->first();

        // Assert — the model graph reflects the seeded rows without a reload hack.
        $this->assertSame(self::QUESTIONS_COUNT, $version->questions->count());

        $optionsThroughGraph = $version->questions->sum(fn (Question $question): int => $question->questionOptions->count());
        $this->assertSame(
            self::QUESTIONS_COUNT * self::OPTIONS_PER_QUESTION,
            $optionsThroughGraph,
            'The Eloquent graph must expose every seeded option.'
        );
    }

    public function test_seeding_does_not_swallow_manually_created_versions(): void
    {
        // Arrange — an unrelated draft version created outside the seeder.
        $draft = AssessmentVersion::create(['version_number' => 13, 'status' => 'draft']);

        // Act
        $this->seed(AssessmentQuestionBankSeeder::class);

        // Assert — the seeder owns version 12 only.
        $this->assertTrue(AssessmentVersion::where('version_number', 13)->exists(), 'The draft version must survive seeding.');
        $this->assertSame(self::VERSION_NUMBER, AssessmentVersion::where('status', 'active')->value('version_number'));
        $this->assertSame(2, AssessmentVersion::count());
    }

    public function test_database_is_usable_after_repeated_seeding(): void
    {
        // Act — three more runs, then a plain read.
        $this->seed(AssessmentQuestionBankSeeder::class);
        $this->seed(AssessmentQuestionBankSeeder::class);
        $this->seed(AssessmentQuestionBankSeeder::class);

        // Assert — the connection is healthy and the counts are unchanged.
        $this->assertSame(self::QUESTIONS_COUNT, Question::count());
        $this->assertSame(self::QUESTIONS_COUNT * self::OPTIONS_PER_QUESTION, QuestionOption::count());
    }

    /**
     * The approved RIASEC balance, checked at the database level so the guard
     * does not depend on the seeder's own validation having run.
     *
     * @return array<string, array{string}>
     */
    public static function riasecCodeProvider(): array
    {
        return [
            'R' => ['R'],
            'I' => ['I'],
            'A' => ['A'],
            'S' => ['S'],
            'E' => ['E'],
            'C' => ['C'],
        ];
    }

    #[DataProvider('riasecCodeProvider')]
    public function test_every_riasec_domain_appears_twelve_times(string $code): void
    {
        // Act
        $count = QuestionOption::where('riasec_code', $code)->count();

        // Assert — spec v1.2 §13: twelve occurrences per domain.
        $this->assertSame(self::OPTIONS_PER_CODE, $count, "RIASEC code {$code} must appear exactly 12 times.");
    }
}
