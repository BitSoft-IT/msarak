<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnswerSaveFailedException extends Exception
{
    public const ERROR_CODE = 'ANSWER_SAVE_FAILED';

    protected int $statusCode = 500;

    public function __construct(string $message = 'تعذر حفظ الإجابة. حاول مرة أخرى.')
    {
        parent::__construct($message);
    }

    public function render(Request $request): JsonResponse
    {
        return new JsonResponse([
            'message' => $this->getMessage(),
            'code' => self::ERROR_CODE,
        ], $this->statusCode);
    }
}
