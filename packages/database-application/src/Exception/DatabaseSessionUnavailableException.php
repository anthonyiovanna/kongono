<?php

declare(strict_types=1);

namespace Kongono\DatabaseApplication\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\DatabaseApplication\Diagnostic\DatabaseApplicationDiagnostic;

/** Reports use of an operation database session after close has begun. */
#[Api(ApiStatus::Public)]
final class DatabaseSessionUnavailableException extends DatabaseApplicationException
{
    public function __construct()
    {
        parent::__construct(DatabaseApplicationDiagnostic::SessionUnavailable, 'session_closed');
    }
}
