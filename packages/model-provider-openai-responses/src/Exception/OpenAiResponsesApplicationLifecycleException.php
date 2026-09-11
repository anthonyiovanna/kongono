<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponses\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelProviderOpenAiResponses\Diagnostic\OpenAiResponsesDiagnostic;
use Throwable;

/** Reports single-use application lifecycle conflicts before another effect. */
#[Api(ApiStatus::Experimental)]
final class OpenAiResponsesApplicationLifecycleException extends OpenAiResponsesException
{
    public static function failed(string $state, ?Throwable $previous = null): self
    {
        return new self(OpenAiResponsesDiagnostic::ApplicationLifecycleViolation, [
            'state' => self::safeToken($state, ['active', 'retired'], 'unsupported_state'),
        ], $previous);
    }
}
