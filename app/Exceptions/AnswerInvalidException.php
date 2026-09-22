<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnswerInvalidException extends Exception
{
    public const ERROR_CODE = 'ANSWER_INVALID';

    protected int $statusCode = 422;

    protected array $errors = [];

    public function __construct(string $message = 'بيانات الإجابة غير صالحة.', array $errors = [])
    {
        parent::__construct($message);
        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function render(Request $request): JsonResponse
    {
        $payload = [
            'message' => $this->getMessage(),
            'code' => self::ERROR_CODE,
        ];

        if (! empty($this->errors)) {
            $payload['errors'] = $this->errors;
        }

        return new JsonResponse($payload, $this->statusCode);
    }
}
