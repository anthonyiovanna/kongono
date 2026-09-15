<?php

declare(strict_types=1);

namespace Kongono\AssetApplication\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetApplication\Diagnostic\AssetApplicationDiagnostic;
use Throwable;

/** Reports explicit native plan build or check failure through a closed stage. */
#[Api(ApiStatus::Internal)]
final class AssetApplicationWorkflowException extends AssetApplicationException
{
    public static function failed(string $stage, ?Throwable $previous = null): self
    {
        $allowed = ['source_read', 'source_parse', 'native_build', 'target_assessment', 'publication', 'current_check'];

        return new self(AssetApplicationDiagnostic::WorkflowFailed, in_array($stage, $allowed, true) ? $stage : 'workflow', $previous);
    }
}
