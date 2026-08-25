<?php

declare(strict_types=1);

namespace Kongono\Database\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Database\Diagnostic\DatabaseDiagnostic;

/** Reports use outside the accepted session, stream, or transaction state. */
#[Api(ApiStatus::Experimental)]
final class InvalidDatabaseStateException extends DatabaseException
{
    public static function lifecycle(): self
    {
        return new self(DatabaseDiagnostic::LifecycleState);
    }
}
