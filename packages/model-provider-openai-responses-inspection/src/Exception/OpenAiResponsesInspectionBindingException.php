<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponsesInspection\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelProviderOpenAiResponsesInspection\Diagnostic\OpenAiResponsesInspectionDiagnostic;
use Throwable;

/** Reports one exact supplied profile or component binding failure. */
#[Api(ApiStatus::Experimental)]
final class OpenAiResponsesInspectionBindingException extends OpenAiResponsesInspectionException
{
    public static function mismatch(string $component, ?Throwable $previous = null): self
    {
        return new self(OpenAiResponsesInspectionDiagnostic::BindingMismatch, self::safe($component), $previous);
    }
}
