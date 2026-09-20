<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Answer extends Model
{
    protected $fillable = [
        'assessment_session_id',
        'question_id',
        'primary_option_id',
        'response_type',
    ];

    public function assessmentSession(): BelongsTo
    {
        return $this->belongsTo(AssessmentSession::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function primaryOption(): BelongsTo
    {
        return $this->belongsTo(QuestionOption::class, 'primary_option_id');
    }

    public function optionRatings(): HasMany
    {
        return $this->hasMany(AnswerOptionRating::class);
    }
}
