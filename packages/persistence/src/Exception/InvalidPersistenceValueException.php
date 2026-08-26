<?php

declare(strict_types=1);

namespace Kongono\Persistence\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Persistence\Diagnostic\PersistenceDiagnostic;

/** Reports a bounded contract-value violation without retaining rejected input. */
#[Api(ApiStatus::Experimental)]
final class InvalidPersistenceValueException extends PersistenceException
{
    public static function validation(): self
    {
        return new self(PersistenceDiagnostic::Validation);
    }
}
