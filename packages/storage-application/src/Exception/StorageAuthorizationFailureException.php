<?php

declare(strict_types=1);

namespace Kongono\StorageApplication\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\StorageApplication\Diagnostic\StorageApplicationDiagnostic;
use Throwable;

/** Reports a failing application authorizer without collaborator details. */
#[Api(ApiStatus::Experimental)]
final class StorageAuthorizationFailureException extends StorageApplicationException
{
    public static function failed(Throwable $previous): self
    {
        return new self(StorageApplicationDiagnostic::AuthorizationFailure, 'collaborator', $previous);
    }
}
