<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * NOTE: no timestamps on this table, per the approved data model —
     * a score row inherits its lifetime from the parent result.
     */
    public function up(): void
    {
        Schema::create('result_scores', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('result_id')
                ->constrained()
                ->restrictOnDelete();
            $table->enum('riasec_code', ['R', 'I', 'A', 'S', 'E', 'C']);
            // The final Score_d for the domain, 0-100 with 2 decimal places.
            // Intermediate equation terms (N_d, K_d, M_d, P_d, Rraw_d, R_d,
            // C_d, W_d) stay in the scoring service; only the historical
            // final output is persisted here.
            $table->decimal('score', 5, 2);

            // Each RIASEC domain appears at most once per result.
            $table->unique(['result_id', 'riasec_code']);
        });

        // FD-3: Normalized RIASEC score must be within 0.00 .. 100.00.
        if (DB::getDriverName() === 'sqlite') {
            DB::statement("CREATE TRIGGER result_scores_score_check_insert BEFORE INSERT ON result_scores FOR EACH ROW WHEN NEW.score < 0 OR NEW.score > 100 BEGIN SELECT RAISE(ABORT, 'CHECK constraint failed: result_scores_score_check'); END;");
            DB::statement("CREATE TRIGGER result_scores_score_check_update BEFORE UPDATE OF score ON result_scores FOR EACH ROW WHEN NEW.score < 0 OR NEW.score > 100 BEGIN SELECT RAISE(ABORT, 'CHECK constraint failed: result_scores_score_check'); END;");
        } else {
            DB::statement('ALTER TABLE result_scores ADD CONSTRAINT result_scores_score_check CHECK (score BETWEEN 0 AND 100)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('result_scores');
    }
};
