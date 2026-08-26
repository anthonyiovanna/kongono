<?php

declare(strict_types=1);

namespace Kongono\Persistence\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Persistence\Diagnostic\PersistenceDiagnostic;

/** Reports a persistence-context lifecycle violation without private state. */
#[Api(ApiStatus::Experimental)]
final class InvalidPersistenceStateException extends PersistenceException
{
    public static function lifecycle(): self
    {
        return new self(PersistenceDiagnostic::LifecycleState);
    }
}
