<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Result extends Model
{
    // Immutable historical record: only created_at exists on this table.
    public const UPDATED_AT = null;

    protected $fillable = [
        'assessment_session_id',
        'catalog_version',
        'scoring_version',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function assessmentSession(): BelongsTo
    {
        return $this->belongsTo(AssessmentSession::class);
    }

    public function resultScores(): HasMany
    {
        return $this->hasMany(ResultScore::class);
    }

    public function resultRecommendations(): HasMany
    {
        return $this->hasMany(ResultRecommendation::class);
    }
}
