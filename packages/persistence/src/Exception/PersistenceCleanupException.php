<?php

declare(strict_types=1);

namespace Kongono\Persistence\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Persistence\Diagnostic\PersistenceDiagnostic;
use Throwable;

/** Retains a cleanup cause while keeping diagnostic presentation bounded. */
#[Api(ApiStatus::Experimental)]
final class PersistenceCleanupException extends PersistenceException
{
    public static function failed(Throwable $previous): self
    {
        return new self(PersistenceDiagnostic::Cleanup, $previous);
    }
}
