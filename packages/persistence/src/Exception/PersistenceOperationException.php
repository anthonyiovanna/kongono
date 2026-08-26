<?php

declare(strict_types=1);

namespace Kongono\Persistence\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Persistence\Diagnostic\PersistenceDiagnostic;
use Throwable;

/** Retains an operational cause while keeping diagnostic presentation bounded. */
#[Api(ApiStatus::Experimental)]
final class PersistenceOperationException extends PersistenceException
{
    public static function failed(Throwable $previous): self
    {
        return new self(PersistenceDiagnostic::Operation, $previous);
    }
}
