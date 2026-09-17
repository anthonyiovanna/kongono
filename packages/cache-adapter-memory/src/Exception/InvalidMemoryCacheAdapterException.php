<?php

declare(strict_types=1);

namespace Kongono\CacheAdapterMemory\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\CacheAdapterMemory\Diagnostic\MemoryCacheAdapterDiagnostic;

/** Reports one bounded configuration or private-state invariant failure. */
#[Api(ApiStatus::Experimental)]
final class InvalidMemoryCacheAdapterException extends MemoryCacheAdapterException
{
    /** @param array<string, string|int|bool> $context */
    public static function forDiagnostic(MemoryCacheAdapterDiagnostic $diagnostic, array $context): self
    {
        return new self($diagnostic, $context);
    }
}
