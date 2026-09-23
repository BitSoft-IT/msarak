<?php

namespace App\Services;

use App\Models\AssessmentSession;
use App\Models\ResultRecommendation;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

class StatisticsService
{
    /**
     * Metrics are event-based: starts use started_at, completions use
     * completed_at, and recommendations use the immutable result created_at.
     *
     * @return array{started_assessments: int, completed_assessments: int, completion_rate: float, top_recommendations: array<int, array{specialization_key: string, name: string, count: int}>}
     */
    public function summarize(?string $from = null, ?string $to = null): array
    {
        $fromDate = $from ? CarbonImmutable::createFromFormat('!Y-m-d', $from)->startOfDay() : null;
        $toDate = $to ? CarbonImmutable::createFromFormat('!Y-m-d', $to)->endOfDay() : null;

        $started = $this->within(AssessmentSession::query(), 'started_at', $fromDate, $toDate)->count();
        $completed = $this->within(
            AssessmentSession::query()->where('status', 'completed')->whereNotNull('completed_at'),
            'completed_at',
            $fromDate,
            $toDate,
        )->count();

        $recommendations = ResultRecommendation::query()
            ->selectRaw('specialization_key, name_snapshot, COUNT(*) as aggregate_count')
            ->join('results', 'results.id', '=', 'result_recommendations.result_id')
            ->when($fromDate, fn (Builder $query) => $query->where('results.created_at', '>=', $fromDate))
            ->when($toDate, fn (Builder $query) => $query->where('results.created_at', '<=', $toDate))
            ->groupBy('specialization_key', 'name_snapshot')
            ->orderByDesc('aggregate_count')
            ->orderBy('specialization_key')
            ->limit(5)
            ->get()
            ->map(fn (ResultRecommendation $row): array => [
                'specialization_key' => $row->specialization_key,
                'name' => $row->name_snapshot,
                'count' => (int) $row->getAttribute('aggregate_count'),
            ])->values()->all();

        return [
            'started_assessments' => $started,
            'completed_assessments' => $completed,
            'completion_rate' => $started === 0 ? 0.0 : round(($completed / $started) * 100, 1),
            'top_recommendations' => $recommendations,
        ];
    }

    private function within(Builder $query, string $column, ?CarbonImmutable $from, ?CarbonImmutable $to): Builder
    {
        return $query
            ->when($from, fn (Builder $builder) => $builder->where($column, '>=', $from))
            ->when($to, fn (Builder $builder) => $builder->where($column, '<=', $to));
    }
}
