<?php

declare(strict_types=1);

namespace Kongono\Database\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Database\Diagnostic\DatabaseDiagnostic;
use Throwable;

/** Wraps one transaction-begin failure without retry. */
#[Api(ApiStatus::Experimental)]
final class DatabaseTransactionBeginException extends DatabaseException
{
    public static function failed(?Throwable $previous = null): self
    {
        return new self(DatabaseDiagnostic::TransactionBegin, $previous);
    }
}
