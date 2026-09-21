<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Raised when no single, valid, published assessment version is available
 * for a student to take.
 *
 * The message rendered to the client is deliberately generic and stable so
 * that data-integrity problems never leak SQL, stack traces, version counts
 * or internal identifiers. The precise cause is only available programmatic-
 * ally through getReason(), for logging and for tests.
 *
 * Follows the approved error contract in
 * docs/02-system-design/04-system-contracts.md: HTTP 409 with the stable
 * application code ASSESSMENT_UNAVAILABLE.
 */
class AssessmentUnavailableException extends Exception
{
    /**
     * Stable application error code consumed by clients and tests.
     */
    public const ERROR_CODE = 'ASSESSMENT_UNAVAILABLE';

    /**
     * No version exists with status = active AND published_at IS NOT NULL.
     */
    public const REASON_NO_PUBLISHED_VERSION = 'no_published_version';

    /**
     * More than one version qualifies as published, which is a data-integrity
     * violation and must never be resolved by silently picking the first row.
     */
    public const REASON_MULTIPLE_PUBLISHED_VERSIONS = 'multiple_published_versions';

    /**
     * HTTP status per the system contracts (409 Conflict: assessment not ready).
     */
    protected int $statusCode = 409;

    protected string $reason;

    protected function __construct(string $reason, string $message, int $statusCode = 409)
    {
        parent::__construct($message);

        $this->reason = $reason;
        $this->statusCode = $statusCode;
    }

    /**
     * No published version is available at all.
     */
    public static function noPublishedVersion(): static
    {
        return new static(
            static::REASON_NO_PUBLISHED_VERSION,
            'لا يتوفر اختبار التقييم حالياً. برجاء المحاولة لاحقاً.'
        );
    }

    /**
     * Several versions qualify as published, so the active one is ambiguous.
     */
    public static function multiplePublishedVersions(): static
    {
        return new static(
            static::REASON_MULTIPLE_PUBLISHED_VERSIONS,
            'لا يتوفر اختبار التقييم حالياً. برجاء المحاولة لاحقاً.'
        );
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Render the approved, safe error envelope. Exposes only a stable code
     * and a generic Arabic message; never SQL, traces or internal details.
     */
    public function render(Request $request): JsonResponse
    {
        return new JsonResponse([
            'message' => $this->getMessage(),
            'code' => static::ERROR_CODE,
        ], $this->statusCode);
    }
}
