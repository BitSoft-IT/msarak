<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuestionOption extends Model
{
    // No timestamps on this table, per the approved data model.
    public $timestamps = false;

    protected $fillable = [
        'question_id',
        'position',
        'option_text',
        'riasec_code',
    ];

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function primaryAnswers(): HasMany
    {
        return $this->hasMany(Answer::class, 'primary_option_id');
    }

    public function optionRatings(): HasMany
    {
        return $this->hasMany(AnswerOptionRating::class);
    }
}
