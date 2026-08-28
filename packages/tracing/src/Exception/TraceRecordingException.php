<?php

declare(strict_types=1);

namespace Kongono\Tracing\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Tracing\Diagnostic\TracingDiagnostic;
use Throwable;

/** Preserves one final trace-recording failure without disclosing it. */
#[Api(ApiStatus::Experimental)]
final class TraceRecordingException extends TracingException
{
    private function __construct(Throwable $previous)
    {
        parent::__construct(TracingDiagnostic::Recording, $previous);
    }

    public static function failed(Throwable $previous): self
    {
        return new self($previous);
    }
}
