<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'position' => ['required', 'integer', 'between:1,18'],
            'scenario' => ['required', 'string', 'max:5000'],
            'options' => ['required', 'array', 'size:4'],
            'options.*.position' => ['required', 'integer', 'between:1,4', 'distinct:strict'],
            'options.*.text' => ['required', 'string', 'max:5000'],
            'options.*.riasec_code' => [
                'required',
                'string',
                Rule::in(['R', 'I', 'A', 'S', 'E', 'C']),
                'distinct:strict',
            ],
        ];
    }
}
