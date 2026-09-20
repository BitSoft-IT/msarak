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
        Schema::create('results', function (Blueprint $table): void {
            $table->id();
            // A session produces zero or one result.
            $table->foreignId('assessment_session_id')
                ->unique()
                ->constrained()
                ->restrictOnDelete();
            $table->string('catalog_version');
            // Version of the scoring equation that produced this result, so
            // historical results stay interpretable if coefficients such as
            // lambda or the rating-influence cap are recalibrated. Set by the
            // scoring service at creation time, not defaulted here.
            $table->string('scoring_version');
            // Only created_at: a result is an immutable historical record.
            $table->timestamp('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('results');
    }
};
