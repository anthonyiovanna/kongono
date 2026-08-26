<?php

declare(strict_types=1);

namespace Kongono\PersistenceApplication\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\PersistenceApplication\Diagnostic\PersistenceApplicationDiagnostic;
use Throwable;

/** Adds one bounded integration-stage meaning while retaining the exact cause. */
#[Api(ApiStatus::Experimental)]
final class PersistenceApplicationWorkflowException extends PersistenceApplicationException
{
    public function __construct(string $failureCategory, ?Throwable $previous = null)
    {
        parent::__construct(PersistenceApplicationDiagnostic::WorkflowFailed, $failureCategory, $previous);
    }
}
