<?php

declare(strict_types=1);

namespace Kongono\DatabaseApplication\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\DatabaseApplication\Diagnostic\DatabaseApplicationDiagnostic;
use Throwable;

/** Adds one bounded integration-stage meaning while retaining the exact cause. */
#[Api(ApiStatus::Public)]
final class DataApplicationWorkflowException extends DatabaseApplicationException
{
    public function __construct(string $failureCategory, ?Throwable $previous = null)
    {
        parent::__construct(DatabaseApplicationDiagnostic::WorkflowFailed, $failureCategory, $previous);
    }
}
