<?php

namespace App\Services;

use App\Exceptions\ResourceNotFoundException;
use App\Exceptions\VersionNotDraftException;
use App\Exceptions\VersionNotPublishableException;
use App\Models\AssessmentVersion;
use App\Models\Question;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssessmentVersionService
{
    private const QUESTION_COUNT = 18;

    private const OPTIONS_PER_QUESTION = 4;

    private const OPTIONS_PER_CODE = 12;

    private const RIASEC_CODES = ['R', 'I', 'A', 'S', 'E', 'C'];

    /**
     * Return the single open draft, or create one optionally cloned from a
     * source version. The version rows are locked so concurrent administrators
     * cannot allocate the same version number or create two drafts.
     *
     * @return array{version: AssessmentVersion, created: bool}
     */
    public function createOrGetDraft(?int $sourceVersionId = null): array
    {
        return DB::transaction(function () use ($sourceVersionId): array {
            $versions = AssessmentVersion::query()->orderBy('id')->lockForUpdate()->get();
            $existingDraft = $versions->firstWhere('status', 'draft');

            if ($existingDraft) {
                return [
                    'version' => $existingDraft->loadCount('questions'),
                    'created' => false,
                ];
            }

            $source = null;
            if ($sourceVersionId !== null) {
                $source = $versions->firstWhere('id', $sourceVersionId);
                if (! $source) {
                    throw new ResourceNotFoundException;
                }
                $source->load(['questions' => fn ($query) => $query->orderBy('position'), 'questions.questionOptions']);
            }

            $version = AssessmentVersion::create([
                'version_number' => ((int) $versions->max('version_number')) + 1,
                'status' => 'draft',
                'published_at' => null,
            ]);

            if ($source) {
                foreach ($source->questions as $sourceQuestion) {
                    $question = $version->questions()->create([
                        'position' => $sourceQuestion->position,
                        'scenario' => $sourceQuestion->scenario,
                    ]);

                    $question->questionOptions()->createMany(
                        $sourceQuestion->questionOptions
                            ->sortBy('position')
                            ->map(fn ($option): array => [
                                'position' => $option->position,
                                'option_text' => $option->option_text,
                                'riasec_code' => $option->riasec_code,
                            ])->values()->all()
                    );
                }
            }

            return [
                'version' => $version->loadCount('questions'),
                'created' => true,
            ];
        });
    }

    /** @param array{position: int, scenario: string, options: array<int, array{position: int, text: string, riasec_code: string}>} $data */
    public function addQuestion(AssessmentVersion $version, array $data): Question
    {
        return DB::transaction(function () use ($version, $data): Question {
            $lockedVersion = $this->lockEditableVersion($version->id);

            if ($lockedVersion->questions()->where('position', $data['position'])->exists()) {
                throw ValidationException::withMessages([
                    'position' => ['يوجد سؤال آخر في هذا الموضع داخل الإصدار.'],
                ]);
            }

            $question = $lockedVersion->questions()->create([
                'position' => $data['position'],
                'scenario' => $data['scenario'],
            ]);
            $this->createOptions($question, $data['options']);

            return $question->load('questionOptions');
        });
    }

    /** @param array{position: int, scenario: string, options: array<int, array{position: int, text: string, riasec_code: string}>} $data */
    public function replaceQuestion(AssessmentVersion $version, Question $question, array $data): Question
    {
        return DB::transaction(function () use ($version, $question, $data): Question {
            $lockedVersion = $this->lockEditableVersion($version->id);
            $lockedQuestion = Question::query()->whereKey($question->id)->lockForUpdate()->first();

            if (! $lockedQuestion || $lockedQuestion->assessment_version_id !== $lockedVersion->id) {
                throw new ResourceNotFoundException;
            }

            if ($lockedVersion->questions()
                ->where('position', $data['position'])
                ->whereKeyNot($lockedQuestion->id)
                ->exists()) {
                throw ValidationException::withMessages([
                    'position' => ['يوجد سؤال آخر في هذا الموضع داخل الإصدار.'],
                ]);
            }

            $lockedQuestion->questionOptions()->delete();
            $lockedQuestion->update([
                'position' => $data['position'],
                'scenario' => $data['scenario'],
            ]);
            $this->createOptions($lockedQuestion, $data['options']);

            return $lockedQuestion->load('questionOptions');
        });
    }

    public function deleteQuestion(AssessmentVersion $version, Question $question): void
    {
        DB::transaction(function () use ($version, $question): void {
            $lockedVersion = $this->lockEditableVersion($version->id);
            $lockedQuestion = Question::query()->whereKey($question->id)->lockForUpdate()->first();

            if (! $lockedQuestion || $lockedQuestion->assessment_version_id !== $lockedVersion->id) {
                throw new ResourceNotFoundException;
            }

            $lockedQuestion->questionOptions()->delete();
            $lockedQuestion->delete();
        });
    }

    /** @return array{version: AssessmentVersion, already_published: bool} */
    public function publish(AssessmentVersion $version): array
    {
        return DB::transaction(function () use ($version): array {
            /** @var Collection<int, AssessmentVersion> $versions */
            $versions = AssessmentVersion::query()->orderBy('id')->lockForUpdate()->get();
            $lockedVersion = $versions->firstWhere('id', $version->id);

            if (! $lockedVersion) {
                throw new ResourceNotFoundException;
            }

            if ($lockedVersion->status === 'active') {
                if ($versions->where('status', 'active')->count() !== 1) {
                    throw new VersionNotPublishableException('حالة نشر إصدارات التقييم غير متسقة.');
                }

                return ['version' => $lockedVersion, 'already_published' => true];
            }

            if ($lockedVersion->status !== 'draft') {
                throw new VersionNotDraftException;
            }

            $lockedVersion->load([
                'questions' => fn ($query) => $query->orderBy('position'),
                'questions.questionOptions' => fn ($query) => $query->orderBy('position'),
            ]);
            $this->assertPublishable($lockedVersion);

            AssessmentVersion::query()
                ->where('status', 'active')
                ->whereKeyNot($lockedVersion->id)
                ->update(['status' => 'retired']);

            $lockedVersion->update([
                'status' => 'active',
                'published_at' => now(),
            ]);

            return ['version' => $lockedVersion->fresh(), 'already_published' => false];
        });
    }

    private function lockEditableVersion(int $versionId): AssessmentVersion
    {
        $version = AssessmentVersion::query()->whereKey($versionId)->lockForUpdate()->first();

        if (! $version) {
            throw new ResourceNotFoundException;
        }

        if ($version->status !== 'draft' || $version->assessmentSessions()->exists()) {
            throw new VersionNotDraftException;
        }

        return $version;
    }

    /** @param array<int, array{position: int, text: string, riasec_code: string}> $options */
    private function createOptions(Question $question, array $options): void
    {
        $question->questionOptions()->createMany(collect($options)->map(fn (array $option): array => [
            'position' => $option['position'],
            'option_text' => $option['text'],
            'riasec_code' => $option['riasec_code'],
        ])->all());
    }

    private function assertPublishable(AssessmentVersion $version): void
    {
        $errors = [];
        $questions = $version->questions;

        if ($questions->count() !== self::QUESTION_COUNT
            || $questions->pluck('position')->sort()->values()->all() !== range(1, self::QUESTION_COUNT)) {
            $errors['questions'][] = 'يجب أن يحتوي الإصدار على 18 سؤالًا مرتبة من 1 إلى 18.';
        }

        $codeCounts = array_fill_keys(self::RIASEC_CODES, 0);
        foreach ($questions as $question) {
            $options = $question->questionOptions;
            if ($options->count() !== self::OPTIONS_PER_QUESTION
                || $options->pluck('position')->sort()->values()->all() !== range(1, self::OPTIONS_PER_QUESTION)
                || $options->pluck('riasec_code')->unique()->count() !== self::OPTIONS_PER_QUESTION) {
                $errors["questions.{$question->position}"][] = 'يجب أن يحتوي السؤال على أربعة خيارات مختلفة بمواضع 1 إلى 4.';
            }

            foreach ($options as $option) {
                if (array_key_exists($option->riasec_code, $codeCounts)) {
                    $codeCounts[$option->riasec_code]++;
                }
            }
        }

        foreach ($codeCounts as $code => $count) {
            if ($count !== self::OPTIONS_PER_CODE) {
                $errors['riasec'][] = "يجب أن يظهر المجال {$code} اثنتي عشرة مرة.";
            }
        }

        if ($errors !== []) {
            throw new VersionNotPublishableException(errors: $errors);
        }
    }
}
