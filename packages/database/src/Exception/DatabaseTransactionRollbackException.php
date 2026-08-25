<?php

declare(strict_types=1);

namespace Kongono\Database\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Database\Diagnostic\DatabaseDiagnostic;
use Throwable;

/** Wraps one terminal rollback failure and requires provider-state retirement. */
#[Api(ApiStatus::Experimental)]
final class DatabaseTransactionRollbackException extends DatabaseException
{
    public static function failed(?Throwable $previous = null): self
    {
        return new self(DatabaseDiagnostic::TransactionRollback, $previous);
    }
}
