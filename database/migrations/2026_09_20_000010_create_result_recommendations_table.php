<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('result_recommendations');
    }
};
