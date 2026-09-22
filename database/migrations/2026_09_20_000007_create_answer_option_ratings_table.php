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
     * Optional side ratings a student may give to any of the four options
     * alongside the main pick, per assessment spec v1.2. Absence of a row
     * means "not rated", which is deliberately distinct from rating = 0
     * ("neutral / unsure").
     */
    public function up(): void
    {
        Schema::create('answer_option_ratings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('answer_id')
                ->constrained()
                ->restrictOnDelete();
            $table->foreignId('question_option_id')
                ->constrained()
                ->restrictOnDelete();
            $table->tinyInteger('rating');
            $table->timestamps();

            // A student may rate each option at most once per answer.
            $table->unique(['answer_id', 'question_option_id']);
        });

        // The rating scale is the closed set {-2, -1, 0, 1, 2}. Laravel's
        // Schema Builder offers no first-class CHECK expression, so the
        // constraint is declared as raw DDL; MySQL 8 enforces it natively.
        if (DB::getDriverName() === 'sqlite') {
            DB::statement("CREATE TRIGGER answer_option_ratings_rating_check_insert BEFORE INSERT ON answer_option_ratings FOR EACH ROW WHEN NEW.rating < -2 OR NEW.rating > 2 BEGIN SELECT RAISE(ABORT, 'CHECK constraint failed: answer_option_ratings_rating_check'); END;");
            DB::statement("CREATE TRIGGER answer_option_ratings_rating_check_update BEFORE UPDATE OF rating ON answer_option_ratings FOR EACH ROW WHEN NEW.rating < -2 OR NEW.rating > 2 BEGIN SELECT RAISE(ABORT, 'CHECK constraint failed: answer_option_ratings_rating_check'); END;");
        } else {
            DB::statement('ALTER TABLE answer_option_ratings ADD CONSTRAINT answer_option_ratings_rating_check CHECK (rating BETWEEN -2 AND 2)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('answer_option_ratings');
    }
};
