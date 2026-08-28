<?php

declare(strict_types=1);

namespace Kongono\Tracing\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Tracing\Diagnostic\TracingDiagnostic;
use Throwable;

/** Preserves one caller-owned collaborator failure without disclosing it. */
#[Api(ApiStatus::Experimental)]
final class TracingCollaboratorException extends TracingException
{
    private function __construct(Throwable $previous)
    {
        parent::__construct(TracingDiagnostic::Collaborator, $previous);
    }

    public static function clock(Throwable $previous): self
    {
        return new self($previous);
    }

    public static function identifierSource(Throwable $previous): self
    {
        return new self($previous);
    }

    public static function sampler(Throwable $previous): self
    {
        return new self($previous);
    }
}
