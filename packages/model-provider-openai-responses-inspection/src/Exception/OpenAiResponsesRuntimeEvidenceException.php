<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponsesInspection\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelProviderOpenAiResponsesInspection\Diagnostic\OpenAiResponsesInspectionDiagnostic;
use Throwable;

/** Reports invalid detached completed runtime relationships. */
#[Api(ApiStatus::Experimental)]
final class OpenAiResponsesRuntimeEvidenceException extends OpenAiResponsesInspectionException
{
    public static function mismatch(string $component, ?Throwable $previous = null): self
    {
        return new self(OpenAiResponsesInspectionDiagnostic::CompletedRuntimeMismatch, self::safe($component), $previous);
    }
}
