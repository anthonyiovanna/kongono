<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponsesInspection\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelProviderOpenAiResponsesInspection\Diagnostic\OpenAiResponsesInspectionDiagnostic;
use Throwable;

/** Reports neutral projection failure without recording or foreign messages. */
#[Api(ApiStatus::Experimental)]
final class OpenAiResponsesInspectionProjectionException extends OpenAiResponsesInspectionException
{
    public static function failed(string $component, ?Throwable $previous = null): self
    {
        return new self(OpenAiResponsesInspectionDiagnostic::ProjectionFailure, self::safe($component), $previous);
    }
}
