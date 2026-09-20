<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('answers');
    }
};
