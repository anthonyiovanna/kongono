<?php

declare(strict_types=1);

namespace Kongono\Database\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Database\Diagnostic\DatabaseDiagnostic;

/** Reports a request outside the exact opened-session capability set. */
#[Api(ApiStatus::Experimental)]
final class UnsupportedDatabaseCapabilityException extends DatabaseException
{
    public static function requested(): self
    {
        return new self(DatabaseDiagnostic::UnsupportedCapability);
    }
}
