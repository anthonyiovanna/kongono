<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponses\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelProviderOpenAiResponses\Diagnostic\OpenAiResponsesDiagnostic;
use Throwable;

/** Identifies one failed explicit application build or check stage. */
#[Api(ApiStatus::Experimental)]
final class OpenAiResponsesApplicationWorkflowException extends OpenAiResponsesException
{
    public static function failed(string $stage, ?Throwable $previous = null): self
    {
        $allowed = ['model_application_build', 'model_application_check', 'application_profile', 'application_create'];

        return new self(OpenAiResponsesDiagnostic::ApplicationWorkflowFailed, [
            'stage' => self::safeToken($stage, $allowed, 'unsupported_stage'),
        ], $previous);
    }
}
