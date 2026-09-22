<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ResourceNotFoundException extends Exception
{
    public const ERROR_CODE = 'RESOURCE_NOT_FOUND';

    protected int $statusCode = 404;

    public function __construct(string $message = 'المورد غير موجود.')
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
