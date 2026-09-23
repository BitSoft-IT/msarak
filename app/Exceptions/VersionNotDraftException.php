<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VersionNotDraftException extends Exception
{
    public const ERROR_CODE = 'VERSION_NOT_DRAFT';

    public function __construct(string $message = 'إصدار التقييم غير قابل للتعديل.')
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
