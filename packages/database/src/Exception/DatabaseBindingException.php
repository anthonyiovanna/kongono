<?php

declare(strict_types=1);

namespace Kongono\Database\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Database\Diagnostic\DatabaseDiagnostic;
use Throwable;

/** Wraps one exact parameter-binding failure without copying provider detail. */
#[Api(ApiStatus::Experimental)]
final class DatabaseBindingException extends DatabaseException
{
    public static function failed(?Throwable $previous = null): self
    {
        return new self(DatabaseDiagnostic::ParameterBinding, $previous);
    }
}
