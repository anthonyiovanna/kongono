<?php

declare(strict_types=1);

namespace Kongono\Database\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Database\Diagnostic\DatabaseDiagnostic;
use Throwable;

/** Reports result-shape, metadata, decoding, or access failure without result data. */
#[Api(ApiStatus::Experimental)]
final class DatabaseResultException extends DatabaseException
{
    public static function failed(?Throwable $previous = null): self
    {
        return new self(DatabaseDiagnostic::Result, $previous);
    }

    public static function access(): self
    {
        return new self(DatabaseDiagnostic::Result);
    }
}
