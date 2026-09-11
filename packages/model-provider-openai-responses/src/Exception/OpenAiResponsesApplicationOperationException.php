<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponses\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelProviderOpenAiResponses\Diagnostic\OpenAiResponsesDiagnostic;
use Throwable;

/** Preserves one lower operation failure behind a closed application stage. */
#[Api(ApiStatus::Experimental)]
final class OpenAiResponsesApplicationOperationException extends OpenAiResponsesException
{
    public static function failed(string $stage, Throwable $previous): self
    {
        $allowed = ['context', 'application_start', 'invocation_run'];

        return new self(OpenAiResponsesDiagnostic::ApplicationOperationFailed, [
            'stage' => self::safeToken($stage, $allowed, 'unsupported_stage'),
        ], $previous);
    }
}
