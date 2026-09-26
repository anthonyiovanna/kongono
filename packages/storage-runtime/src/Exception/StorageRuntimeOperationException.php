<?php

declare(strict_types=1);

namespace Kongono\StorageRuntime\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\StorageRuntime\Diagnostic\StorageRuntimeDiagnostic;
use Throwable;

/** Reports terminal adapter execution or same-instance reentrancy failure. */
#[Api(ApiStatus::Experimental)]
final class StorageRuntimeOperationException extends StorageRuntimeException
{
    public static function adapterFailed(string $operation, Throwable $previous): self
    {
        return new self(StorageRuntimeDiagnostic::AdapterFailed, ['violation' => 'adapter_failed', 'operation' => self::op($operation)], $previous);
    }

    public static function reentrant(string $operation): self
    {
        return new self(StorageRuntimeDiagnostic::ReentrantOperation, ['violation' => 'reentrant_operation', 'operation' => self::op($operation), 'active' => true]);
    }

    private static function op(string $operation): string
    {
        return in_array($operation, ['read', 'create', 'replace', 'delete', 'list'], true) ? $operation : 'unsupported_operation';
    }
}
