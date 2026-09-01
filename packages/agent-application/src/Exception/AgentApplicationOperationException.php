<?php

declare(strict_types=1);

namespace Kongono\AgentApplication\Exception;

use Kongono\AgentApplication\Diagnostic\AgentApplicationDiagnostic;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Throwable;

/** Preserves a pre-runtime operation failure without disclosing collaborator data. */
#[Api(ApiStatus::Experimental)]
final class AgentApplicationOperationException extends AgentApplicationException
{
    public static function failed(string $stage, ?Throwable $previous = null): self
    {
        $allowed = ['input', 'request'];

        return new self(AgentApplicationDiagnostic::LifecycleFailed, ['stage' => in_array($stage, $allowed, true) ? $stage : 'unsupported_stage'], $previous);
    }
}
