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
     * NOTE: this table intentionally has no timestamps, per the approved
     * data model (options are versioned through their parent question).
     */
    public function up(): void
    {
        Schema::create('question_options', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('question_id')
                ->constrained()
                ->restrictOnDelete();
            $table->unsignedInteger('position');
            $table->text('option_text');
            $table->enum('riasec_code', ['R', 'I', 'A', 'S', 'E', 'C']);

            $table->unique(['question_id', 'position']);
        });

        // FD-2: Question options are strictly numbered 1..4.
        if (DB::getDriverName() === 'sqlite') {
            DB::statement("CREATE TRIGGER question_options_position_check_insert BEFORE INSERT ON question_options FOR EACH ROW WHEN NEW.position < 1 OR NEW.position > 4 BEGIN SELECT RAISE(ABORT, 'CHECK constraint failed: question_options_position_check'); END;");
            DB::statement("CREATE TRIGGER question_options_position_check_update BEFORE UPDATE OF position ON question_options FOR EACH ROW WHEN NEW.position < 1 OR NEW.position > 4 BEGIN SELECT RAISE(ABORT, 'CHECK constraint failed: question_options_position_check'); END;");
        } else {
            DB::statement('ALTER TABLE question_options ADD CONSTRAINT question_options_position_check CHECK (position BETWEEN 1 AND 4)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('question_options');
    }
};
