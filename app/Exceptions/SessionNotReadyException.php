<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SessionNotReadyException extends Exception
{
    public const ERROR_CODE = 'SESSION_NOT_READY';

    public function __construct(string $message = 'لم تكتمل إجابات التقييم بعد.')
    {
        parent::__construct($message);
    }

    public function render(Request $request): JsonResponse
    {
        return new JsonResponse([
            'message' => $this->getMessage(),
            'code' => self::ERROR_CODE,
        ], 409);
    }
}
