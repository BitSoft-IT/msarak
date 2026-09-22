<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SessionCompletedException extends Exception
{
    public const ERROR_CODE = 'SESSION_COMPLETED';

    protected int $statusCode = 409;

    public function __construct(string $message = 'لا يمكن تعديل إجابات جلسة مكتملة.')
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
