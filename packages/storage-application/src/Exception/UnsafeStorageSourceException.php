<?php

declare(strict_types=1);

namespace Kongono\StorageApplication\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\StorageApplication\Diagnostic\StorageApplicationDiagnostic;
use Throwable;

/** Reports unsafe source evidence without disclosing paths or bytes. */
#[Api(ApiStatus::Experimental)]
final class UnsafeStorageSourceException extends StorageApplicationException
{
    public static function unsafe(string $stage, ?Throwable $previous = null): self
    {
        $allowed = ['missing', 'linked', 'hard_linked', 'nonordinary', 'unreadable', 'oversized', 'changed_during_read', 'invalid_bytes'];
        return new self(StorageApplicationDiagnostic::UnsafeSource, in_array($stage, $allowed, true) ? $stage : 'unsafe_source', $previous);
    }
}
