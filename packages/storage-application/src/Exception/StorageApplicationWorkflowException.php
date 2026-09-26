<?php

declare(strict_types=1);

namespace Kongono\StorageApplication\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\StorageApplication\Diagnostic\StorageApplicationDiagnostic;
use Throwable;

/** Reports workflow and exact-current failures without foreign details. */
#[Api(ApiStatus::Experimental)]
final class StorageApplicationWorkflowException extends StorageApplicationException
{
    public static function workflow(string $stage, ?Throwable $previous = null): self
    {
        return new self(StorageApplicationDiagnostic::WorkflowFailed, in_array($stage, ['expected', 'currentness'], true) ? $stage : 'workflow', $previous);
    }
    public static function publication(?Throwable $previous = null): self
    {
        return new self(StorageApplicationDiagnostic::PublicationFailed, 'publication', $previous);
    }
}
