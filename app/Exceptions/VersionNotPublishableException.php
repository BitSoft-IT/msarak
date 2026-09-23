<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VersionNotPublishableException extends Exception
{
    public const ERROR_CODE = 'VERSION_NOT_PUBLISHABLE';

    /** @param array<string, array<int, string>> $errors */
    public function __construct(
        string $message = 'إصدار التقييم غير جاهز للنشر.',
        private readonly array $errors = [],
    ) {
        parent::__construct($message);
    }

    public function render(Request $request): JsonResponse
    {
        $payload = [
            'message' => $this->getMessage(),
            'code' => self::ERROR_CODE,
        ];

        if ($this->errors !== []) {
            $payload['errors'] = $this->errors;
        }

        return new JsonResponse($payload, 422);
    }
}
