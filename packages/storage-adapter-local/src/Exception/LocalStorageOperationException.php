<?php

declare(strict_types=1);

namespace Kongono\StorageAdapterLocal\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\StorageAdapterLocal\Diagnostic\LocalStorageAdapterDiagnostic;
use Throwable;

/** Reports unexpected operation or cleanup failure without native disclosure. */
#[Api(ApiStatus::Experimental)]
final class LocalStorageOperationException extends LocalStorageAdapterException
{
    public static function failed(string $violation, string $operation, ?Throwable $previous = null): self
    {
        return new self(LocalStorageAdapterDiagnostic::OperationFailure, ['violation' => $violation, 'operation' => $operation], $previous);
    }

    public static function cleanup(string $operation, ?Throwable $previous = null): self
    {
        return new self(LocalStorageAdapterDiagnostic::CleanupFailure, ['violation' => 'cleanup_failed', 'operation' => $operation], $previous);
    }
}
