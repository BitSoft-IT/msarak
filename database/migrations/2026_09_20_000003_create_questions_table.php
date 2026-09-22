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
        Schema::create('questions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('assessment_version_id')
                ->constrained()
                ->restrictOnDelete();
            $table->unsignedInteger('position');
            $table->text('scenario');
            $table->timestamps();

            $table->unique(['assessment_version_id', 'position']);
        });

        // FD-2: Questions in an assessment version are strictly numbered 1..18.
        if (DB::getDriverName() === 'sqlite') {
            DB::statement("CREATE TRIGGER questions_position_check_insert BEFORE INSERT ON questions FOR EACH ROW WHEN NEW.position < 1 OR NEW.position > 18 BEGIN SELECT RAISE(ABORT, 'CHECK constraint failed: questions_position_check'); END;");
            DB::statement("CREATE TRIGGER questions_position_check_update BEFORE UPDATE OF position ON questions FOR EACH ROW WHEN NEW.position < 1 OR NEW.position > 18 BEGIN SELECT RAISE(ABORT, 'CHECK constraint failed: questions_position_check'); END;");
        } else {
            DB::statement('ALTER TABLE questions ADD CONSTRAINT questions_position_check CHECK (position BETWEEN 1 AND 18)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
