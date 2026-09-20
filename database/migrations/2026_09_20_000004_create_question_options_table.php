<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('question_options');
    }
};
