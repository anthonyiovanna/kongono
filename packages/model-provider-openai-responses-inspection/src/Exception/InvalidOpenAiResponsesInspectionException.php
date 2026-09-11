<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponsesInspection\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelProviderOpenAiResponsesInspection\Diagnostic\OpenAiResponsesInspectionDiagnostic;
use Throwable;

/** Rejects malformed package-owned inspection values. */
#[Api(ApiStatus::Experimental)]
final class InvalidOpenAiResponsesInspectionException extends OpenAiResponsesInspectionException
{
    public static function invalid(string $component, ?Throwable $previous = null): self
    {
        return new self(OpenAiResponsesInspectionDiagnostic::InvalidInspection, self::safe($component), $previous);
    }
}
