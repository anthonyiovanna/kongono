<?php

declare(strict_types=1);

namespace Kongono\Persistence\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Persistence\Diagnostic\PersistenceDiagnostic;
use Throwable;

/** Reports terminal uncertainty while retaining its cause outside Safe presentation. */
#[Api(ApiStatus::Experimental)]
final class PersistenceUncertainStateException extends PersistenceException
{
    public static function failed(Throwable $previous): self
    {
        return new self(PersistenceDiagnostic::TerminalUncertainty, $previous);
    }
}
