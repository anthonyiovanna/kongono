<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressApplication\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressApplication\Diagnostic\NetworkEgressApplicationDiagnostic;
use Throwable;

/** Wraps one build stage without disclosing application-owned inputs. */
#[Api(ApiStatus::Experimental)]
final class NetworkEgressApplicationWorkflowException extends NetworkEgressApplicationException
{
    public static function failed(string $stage, ?Throwable $previous = null): self
    {
        $allowed = ['path', 'profile', 'generate', 'validate', 'publish', 'load'];

        return new self(NetworkEgressApplicationDiagnostic::WorkflowFailed, ['stage' => in_array($stage, $allowed, true) ? $stage : 'unsupported_stage'], $previous);
    }
}
