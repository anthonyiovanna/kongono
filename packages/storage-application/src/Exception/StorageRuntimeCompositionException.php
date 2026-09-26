<?php

declare(strict_types=1);

namespace Kongono\StorageApplication\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\StorageApplication\Diagnostic\StorageApplicationDiagnostic;
use Throwable;

/** Reports exact-current runtime composition failure. */
#[Api(ApiStatus::Experimental)]
final class StorageRuntimeCompositionException extends StorageApplicationException
{
    public static function failed(?Throwable $previous = null): self
    {
        return new self(StorageApplicationDiagnostic::RuntimeCompositionFailed, 'composition', $previous);
    }
}
