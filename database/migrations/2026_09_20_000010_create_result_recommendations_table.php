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
     * a recommendation row inherits its lifetime from the parent result.
     */
    public function up(): void
    {
        Schema::create('result_recommendations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('result_id')
                ->constrained()
                ->restrictOnDelete();
            // Key into resources/data/specializations.json — no specializations table.
            $table->string('specialization_key');
            $table->string('name_snapshot');
            $table->unsignedInteger('display_order');
            // Similarity is an internal value, not a certainty percentage.
            $table->decimal('similarity_score', 5, 2);
            $table->text('rationale_snapshot');

            // No duplicated specialization and no duplicated display order per result.
            $table->unique(['result_id', 'specialization_key']);
            $table->unique(['result_id', 'display_order']);
        });

        // FD-3: Recommendation display_order is defined as 1..5.
        if (DB::getDriverName() === 'sqlite') {
            DB::statement("CREATE TRIGGER result_recommendations_display_order_check_insert BEFORE INSERT ON result_recommendations FOR EACH ROW WHEN NEW.display_order < 1 OR NEW.display_order > 5 BEGIN SELECT RAISE(ABORT, 'CHECK constraint failed: result_recommendations_display_order_check'); END;");
            DB::statement("CREATE TRIGGER result_recommendations_display_order_check_update BEFORE UPDATE OF display_order ON result_recommendations FOR EACH ROW WHEN NEW.display_order < 1 OR NEW.display_order > 5 BEGIN SELECT RAISE(ABORT, 'CHECK constraint failed: result_recommendations_display_order_check'); END;");
        } else {
            DB::statement('ALTER TABLE result_recommendations ADD CONSTRAINT result_recommendations_display_order_check CHECK (display_order BETWEEN 1 AND 5)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('result_recommendations');
    }
};
