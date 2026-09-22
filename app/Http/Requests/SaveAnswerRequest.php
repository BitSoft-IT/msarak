<?php

namespace App\Http\Requests;

use App\Exceptions\AnswerInvalidException;
use App\Exceptions\ResourceNotFoundException;
use App\Exceptions\SessionCompletedException;
use App\Models\AssessmentSession;
use App\Models\Question;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class SaveAnswerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $session = $this->route('assessmentSession');

        if ($session instanceof AssessmentSession) {
            if ($session->user_id !== $this->user()?->id) {
                throw new ResourceNotFoundException();
            }

            if ($session->status === 'completed') {
                throw new SessionCompletedException();
            }
        }

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'primary_option_id' => ['nullable', 'integer'],
            'none_selected' => ['nullable', 'boolean'],
            'unable_to_judge' => ['nullable', 'boolean'],
            'ratings' => ['nullable', 'array'],
            'ratings.*.option_id' => ['required_with:ratings', 'integer'],
            'ratings.*.rating' => ['required_with:ratings', 'integer', 'between:-2,2'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $session = $this->route('assessmentSession');
            $question = $this->route('question');

            // 1. Question must belong to the session's assessment version
            if ($question instanceof Question && $session instanceof AssessmentSession) {
                if ($question->assessment_version_id !== $session->assessment_version_id) {
                    $validator->errors()->add('question_id', 'السؤال لا ينتمي إلى إصدار جلسة التقييم.');
                }
            }

            $primaryOptionId = $this->input('primary_option_id');
            $noneSelected = filter_var($this->input('none_selected'), FILTER_VALIDATE_BOOLEAN);
            $unableToJudge = filter_var($this->input('unable_to_judge'), FILTER_VALIDATE_BOOLEAN);

            // 2. Exactly one response state must be selected
            $stateCount = 0;
            if (! is_null($primaryOptionId)) {
                $stateCount++;
            }
            if ($noneSelected) {
                $stateCount++;
            }
            if ($unableToJudge) {
                $stateCount++;
            }

            if ($stateCount !== 1) {
                $validator->errors()->add(
                    'response_type',
                    'يجب اختيار حالة إجابة واحدة فقط من: اختيار أساسي، أو لا ينطبق أي خيار، أو تعذر الحكم.'
                );
            }

            // 3. Primary option must belong to the question
            if (! is_null($primaryOptionId) && $question instanceof Question) {
                $optionExists = $question->questionOptions()->where('id', $primaryOptionId)->exists();
                if (! $optionExists) {
                    $validator->errors()->add('primary_option_id', 'الاختيار غير صالح لهذا السؤال.');
                }
            }

            // 4. If unable_to_judge is true, ratings must be empty or omitted
            $ratings = $this->input('ratings');
            if ($unableToJudge && ! empty($ratings)) {
                $validator->errors()->add('ratings', 'لا يمكن إرسال تقييمات عند تعذر الحكم.');
            }

            // 5. Validate ratings options belonging to question and uniqueness
            if (! empty($ratings) && is_array($ratings) && $question instanceof Question) {
                $validOptionIds = $question->questionOptions()->pluck('id')->all();
                $seenOptionIds = [];

                foreach ($ratings as $index => $ratingItem) {
                    if (! is_array($ratingItem)) {
                        continue;
                    }

                    $optId = $ratingItem['option_id'] ?? null;
                    if ($optId !== null) {
                        if (! in_array($optId, $validOptionIds, true)) {
                            $validator->errors()->add("ratings.{$index}.option_id", 'الخيار لا يتبع هذا السؤال.');
                        }

                        if (in_array($optId, $seenOptionIds, true)) {
                            $validator->errors()->add("ratings.{$index}.option_id", 'لا يجوز تكرار الخيار داخل التقييمات.');
                        }

                        $seenOptionIds[] = $optId;
                    }
                }
            }
        });
    }

    /**
     * Handle a failed validation attempt.
     *
     * @throws AnswerInvalidException
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new AnswerInvalidException('بيانات الإجابة غير صالحة.', $validator->errors()->toArray());
    }
}
