<?php

declare(strict_types=1);

namespace Kongono\ViewApplication\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ViewApplication\Diagnostic\ViewApplicationDiagnostic;
use Throwable;

/** Reports deterministic plan build or check failure through a closed stage. */
#[Api(ApiStatus::Internal)]
final class ViewApplicationWorkflowException extends ViewApplicationException
{
    public static function failed(string $stage, ?Throwable $previous = null): self
    {
        $allowed = ['source_read', 'source_parse', 'artifact_build', 'target_assessment', 'publication', 'current_check'];

        return new self(ViewApplicationDiagnostic::WorkflowFailed, in_array($stage, $allowed, true) ? $stage : 'workflow', $previous);
    }
}
