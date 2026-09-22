<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('answers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('assessment_session_id')
                ->constrained()
                ->restrictOnDelete();
            $table->foreignId('question_id')
                ->constrained()
                ->restrictOnDelete();
            // The student's main pick among the question's options.
            // NULL when response_type is "none" or "cannot_judge".
            $table->foreignId('primary_option_id')
                ->nullable()
                ->constrained('question_options')
                ->restrictOnDelete();
            // option = one of the four options chosen as the main pick.
            // none = "none of these behaviors describe me".
            // cannot_judge = "I cannot judge this situation".
            $table->enum('response_type', ['option', 'none', 'cannot_judge']);
            $table->timestamps();

            // Only one effective answer per question within a session.
            $table->unique(['assessment_session_id', 'question_id']);
        });

        // FD-1: primary_option_id must match response_type per spec v1.2:
        // - 'option' requires a primary_option_id.
        // - 'none' and 'cannot_judge' forbid a primary_option_id (must be NULL).
        if (DB::getDriverName() === 'sqlite') {
            DB::statement("CREATE TRIGGER answers_response_type_primary_option_check_insert BEFORE INSERT ON answers FOR EACH ROW WHEN NOT (NEW.response_type IS NOT NULL AND ((NEW.response_type = 'option' AND NEW.primary_option_id IS NOT NULL) OR (NEW.response_type IN ('none', 'cannot_judge') AND NEW.primary_option_id IS NULL))) BEGIN SELECT RAISE(ABORT, 'CHECK constraint failed: answers_response_type_primary_option_check'); END;");
            DB::statement("CREATE TRIGGER answers_response_type_primary_option_check_update BEFORE UPDATE OF response_type, primary_option_id ON answers FOR EACH ROW WHEN NOT (NEW.response_type IS NOT NULL AND ((NEW.response_type = 'option' AND NEW.primary_option_id IS NOT NULL) OR (NEW.response_type IN ('none', 'cannot_judge') AND NEW.primary_option_id IS NULL))) BEGIN SELECT RAISE(ABORT, 'CHECK constraint failed: answers_response_type_primary_option_check'); END;");
        } else {
            DB::statement("ALTER TABLE answers ADD CONSTRAINT answers_response_type_primary_option_check CHECK (
                (response_type IS NOT NULL) AND (
                    (response_type = 'option' AND primary_option_id IS NOT NULL)
                    OR
                    (response_type IN ('none', 'cannot_judge') AND primary_option_id IS NULL)
                )
            )");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('answers');
    }
};
