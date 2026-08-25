<?php

declare(strict_types=1);

namespace Kongono\Database\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Database\Diagnostic\DatabaseDiagnostic;
use Throwable;

/** Wraps explicit cleanup failure while leaving uncertain state visible. */
#[Api(ApiStatus::Experimental)]
final class DatabaseCleanupException extends DatabaseException
{
    public static function failed(?Throwable $previous = null): self
    {
        return new self(DatabaseDiagnostic::Cleanup, $previous);
    }
}
