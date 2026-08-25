<?php

declare(strict_types=1);

namespace Kongono\Database\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Database\Diagnostic\DatabaseDiagnostic;
use Throwable;

/** Wraps one provider execution failure without SQL, values, or provider detail. */
#[Api(ApiStatus::Experimental)]
final class DatabaseExecutionException extends DatabaseException
{
    public static function failed(?Throwable $previous = null): self
    {
        return new self(DatabaseDiagnostic::Execution, $previous);
    }
}
