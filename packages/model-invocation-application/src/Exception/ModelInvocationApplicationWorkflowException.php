<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationApplication\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationApplication\Diagnostic\ModelInvocationApplicationDiagnostic;
use Throwable;

/** Wraps one workflow stage without disclosing application-owned inputs. */
#[Api(ApiStatus::Experimental)]
final class ModelInvocationApplicationWorkflowException extends ModelInvocationApplicationException
{
    public static function failed(string $stage, ?Throwable $previous = null): self
    {
        $allowed = ['path', 'artifact_snapshot', 'profile', 'generate', 'publish', 'generated_snapshot', 'load', 'reverify'];

        return new self(ModelInvocationApplicationDiagnostic::WorkflowFailed, ['stage' => in_array($stage, $allowed, true) ? $stage : 'unsupported_stage'], $previous);
    }
}
