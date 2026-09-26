<?php

declare(strict_types=1);

namespace Kongono\StorageApplication\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\StorageApplication\Diagnostic\StorageApplicationDiagnostic;

/** Reports one explicit denied operation without request details. */
#[Api(ApiStatus::Experimental)]
final class StorageAuthorizationDeniedException extends StorageApplicationException
{
    public static function denied(): self
    {
        return new self(StorageApplicationDiagnostic::AuthorizationDenied, 'denied');
    }
}
