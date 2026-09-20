<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResultRecommendation extends Model
{
    // No timestamps on this table, per the approved data model.
    public $timestamps = false;

    protected $fillable = [
        'result_id',
        'specialization_key',
        'name_snapshot',
        'display_order',
        'similarity_score',
        'rationale_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'similarity_score' => 'decimal:2',
        ];
    }

    public function result(): BelongsTo
    {
        return $this->belongsTo(Result::class);
    }
}
