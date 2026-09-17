<?php

declare(strict_types=1);

namespace Kongono\CacheRuntime\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\CacheRuntime\Diagnostic\CacheRuntimeDiagnostic;
use Throwable;

/** Reports terminal adapter execution or same-instance reentrancy failure. */
#[Api(ApiStatus::Experimental)]
final class CacheRuntimeOperationException extends CacheRuntimeException
{
    private const array OPERATIONS = ['lookup', 'store', 'replace', 'remove'];

    public static function adapterFailed(string $operation, Throwable $previous): self
    {
        return new self(CacheRuntimeDiagnostic::AdapterFailed, [
            'violation' => 'adapter_failed',
            'operation' => self::operation($operation),
        ], $previous);
    }

    public static function reentrant(string $operation): self
    {
        return new self(CacheRuntimeDiagnostic::ReentrantOperation, [
            'violation' => 'reentrant_operation',
            'operation' => self::operation($operation),
            'active' => true,
        ]);
    }

    private static function operation(string $operation): string
    {
        return in_array($operation, self::OPERATIONS, true) ? $operation : 'unsupported_operation';
    }
}
