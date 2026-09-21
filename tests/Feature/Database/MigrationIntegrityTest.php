<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Q-02 (Phase 2) — Migration and schema integrity.
 *
 * Verifies that the migrated schema matches specification v1.2: the nine
 * domain tables exist with the expected columns, the legacy closest/least
 * answer model is gone, the timestamp policy per table is honoured, and the
 * enum columns carry exactly the approved value sets.
 *
 * The spec source of truth for the answer model is
 * docs/04-assessment/question_bank_specification.md §30/§30.1, which
 * supersedes the older closest/least design in the data-model document.
 * Production code is never modified by these tests.
 */
class MigrationIntegrityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The nine domain tables mandated by the approved data model.
     *
     * Laravel's operational tables (password_reset_tokens, sessions, cache,
     * jobs) are deliberately excluded — they are not domain entities.
     */
    private const DOMAIN_TABLES = [
        'users',
        'assessment_versions',
        'questions',
        'question_options',
        'assessment_sessions',
        'answers',
        'answer_option_ratings',
        'results',
        'result_scores',
        'result_recommendations',
    ];

    public function test_all_nine_domain_tables_exist(): void
    {
        // Arrange — RefreshDatabase has already built the schema from scratch.

        // Act — probe each required table directly in the database.
        $missing = [];
        foreach (self::DOMAIN_TABLES as $table) {
            if (! Schema::hasTable($table)) {
                $missing[] = $table;
            }
        }

        // Assert
        $this->assertSame([], $missing, 'Every approved domain table must exist after a fresh migration.');
    }

    public function test_no_specializations_table_exists(): void
    {
        // Act
        $exists = Schema::hasTable('specializations');

        // Assert — the catalog is resources/data/specializations.json by design.
        $this->assertFalse($exists, 'Specializations must live in a JSON file, never in a table.');
    }

    public function test_question_options_has_no_legacy_weight_columns(): void
    {
        // Act
        $columns = Schema::getColumnListing('question_options');

        // Assert — spec v1.2 §30 removed closest_weight and least_weight.
        $this->assertNotContains('closest_weight', $columns, 'closest_weight is a legacy column and must not exist.');
        $this->assertNotContains('least_weight', $columns, 'least_weight is a legacy column and must not exist.');
    }

    public function test_answers_uses_the_primary_response_model_not_legacy_columns(): void
    {
        // Act
        $columns = Schema::getColumnListing('answers');

        // Assert — spec v1.2 §30.1 replaced closest/least with primary + type.
        $this->assertContains('primary_option_id', $columns, 'primary_option_id is required by spec v1.2.');
        $this->assertContains('response_type', $columns, 'response_type is required by spec v1.2.');
        $this->assertNotContains('closest_option_id', $columns, 'closest_option_id is a legacy column.');
        $this->assertNotContains('least_option_id', $columns, 'least_option_id is a legacy column.');
        $this->assertNotContains('is_skipped', $columns, 'is_skipped is a legacy column.');
    }

    public function test_result_scores_stores_one_final_score_per_domain(): void
    {
        // Act
        $columns = Schema::getColumnListing('result_scores');

        // Assert — only the final normalized score is persisted.
        $this->assertContains('score', $columns, 'The final score column is required.');
        $this->assertNotContains('raw_score', $columns, 'Intermediate terms stay in the scoring service.');
        $this->assertNotContains('normalized_score', $columns, 'The single final column replaces this legacy pair.');
    }

    public function test_question_options_has_no_timestamp_columns(): void
    {
        // Act
        $columns = Schema::getColumnListing('question_options');

        // Assert — per the approved data model, options inherit their
        // lifetime from the parent question.
        $this->assertNotContains('created_at', $columns, 'question_options must not have timestamps.');
        $this->assertNotContains('updated_at', $columns, 'question_options must not have timestamps.');
    }

    public function test_result_scores_has_no_timestamp_columns(): void
    {
        // Act
        $columns = Schema::getColumnListing('result_scores');

        // Assert
        $this->assertNotContains('created_at', $columns, 'result_scores must not have timestamps.');
        $this->assertNotContains('updated_at', $columns, 'result_scores must not have timestamps.');
    }

    public function test_result_recommendations_has_no_timestamp_columns(): void
    {
        // Act
        $columns = Schema::getColumnListing('result_recommendations');

        // Assert
        $this->assertNotContains('created_at', $columns, 'result_recommendations must not have timestamps.');
        $this->assertNotContains('updated_at', $columns, 'result_recommendations must not have timestamps.');
    }

    public function test_results_has_created_at_only(): void
    {
        // Act
        $columns = Schema::getColumnListing('results');

        // Assert — a result is an immutable historical record.
        $this->assertContains('created_at', $columns, 'A result must record when it was produced.');
        $this->assertNotContains('updated_at', $columns, 'A result is immutable and must never carry updated_at.');
    }

    public function test_results_records_catalog_and_scoring_versions(): void
    {
        // Act
        $columns = Schema::getColumnListing('results');

        // Assert — historical results stay interpretable after recalibration.
        $this->assertContains('catalog_version', $columns, 'The catalog version used must be frozen on the result.');
        $this->assertContains('scoring_version', $columns, 'The scoring equation version must be frozen on the result.');
    }

    public function test_assessment_versions_status_is_indexed(): void
    {
        // Act
        $indexes = DB::select('SHOW INDEX FROM assessment_versions');
        $columns = array_map(static fn (object $index): string => $index->Column_name, $indexes);

        // Assert — the active-version lookup is the hot path for publishing.
        $this->assertContains('status', array_unique($columns), 'The status column must be indexed.');
        $this->assertContains('version_number', array_unique($columns), 'The version number lookup must be indexed.');
    }

    /**
     * @return array<string, array{string, string, list<string>}>
     */
    public static function enumColumnProvider(): array
    {
        return [
            'users.role' => ['users', 'role', ['student', 'admin']],
            'assessment_versions.status' => ['assessment_versions', 'status', ['draft', 'active', 'retired']],
            'question_options.riasec_code' => ['question_options', 'riasec_code', ['R', 'I', 'A', 'S', 'E', 'C']],
            'assessment_sessions.status' => ['assessment_sessions', 'status', ['in_progress', 'completed']],
            'answers.response_type' => ['answers', 'response_type', ['option', 'none', 'cannot_judge']],
            'result_scores.riasec_code' => ['result_scores', 'riasec_code', ['R', 'I', 'A', 'S', 'E', 'C']],
        ];
    }

    #[DataProvider('enumColumnProvider')]
    public function test_enum_columns_carry_exactly_the_approved_values(string $table, string $column, array $expected): void
    {
        // Arrange — read the declared type straight from the information schema.
        $definition = DB::table('information_schema.columns')
            ->where('table_schema', config('database.connections.mysql.database'))
            ->where('table_name', $table)
            ->where('column_name', $column)
            ->value('column_type');

        // Act
        preg_match_all("/'([^']+)'/", (string) $definition, $matches);
        $actual = $matches[1];

        // Assert
        $this->assertSame($expected, $actual, "The enum on {$table}.{$column} must list exactly the approved values.");
    }

    public function test_answer_option_ratings_rating_check_constraint_exists(): void
    {
        // Arrange — read the table's constraints from the information schema.
        // Column names are uppercase in MySQL's information_schema.
        $constraints = DB::select(
            'SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS'
            .' WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_TYPE = ?',
            [config('database.connections.mysql.database'), 'answer_option_ratings', 'CHECK']
        );

        // Act
        $names = array_map(static fn (object $row): string => $row->CONSTRAINT_NAME, $constraints);
        $found = in_array('answer_option_ratings_rating_check', $names, true);

        // Assert — the closed rating set {-2..+2} is enforced by the engine.
        $this->assertTrue($found, 'The CHECK constraint guarding rating BETWEEN -2 AND 2 must exist.');
    }
}
