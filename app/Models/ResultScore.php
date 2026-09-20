<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResultScore extends Model
{
    // No timestamps on this table, per the approved data model.
    public $timestamps = false;

    protected $fillable = [
        'result_id',
        'riasec_code',
        'score',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'decimal:2',
        ];
    }

    public function result(): BelongsTo
    {
        return $this->belongsTo(Result::class);
    }
}
