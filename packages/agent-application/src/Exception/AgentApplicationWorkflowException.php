<?php

declare(strict_types=1);

namespace Kongono\AgentApplication\Exception;

use Kongono\AgentApplication\Diagnostic\AgentApplicationDiagnostic;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Throwable;

/** Wraps one build/check stage without disclosing caller-owned inputs. */
#[Api(ApiStatus::Experimental)]
final class AgentApplicationWorkflowException extends AgentApplicationException
{
    public static function failed(string $stage, ?Throwable $previous = null): self
    {
        $allowed = ['path', 'declaration_snapshot', 'profile', 'generate', 'publish', 'generated_snapshot', 'load', 'reverify'];

        return new self(AgentApplicationDiagnostic::WorkflowFailed, ['stage' => in_array($stage, $allowed, true) ? $stage : 'unsupported_stage'], $previous);
    }
}
