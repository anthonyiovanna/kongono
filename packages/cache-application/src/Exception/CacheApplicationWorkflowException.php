<?php

declare(strict_types=1);

namespace Kongono\CacheApplication\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\CacheApplication\Diagnostic\CacheApplicationDiagnostic;
use Throwable;

/** Reports workflow and exact-current failures without foreign details. */
#[Api(ApiStatus::Experimental)]
final class CacheApplicationWorkflowException extends CacheApplicationException
{
    public static function workflow(string $stage, ?Throwable $previous = null): self
    {
        return new self(CacheApplicationDiagnostic::WorkflowFailed, $stage, $previous);
    }
    public static function publication(?Throwable $previous = null): self
    {
        return new self(CacheApplicationDiagnostic::PublicationFailed, 'publication', $previous);
    }
}
