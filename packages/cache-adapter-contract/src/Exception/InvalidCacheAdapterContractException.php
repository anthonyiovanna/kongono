<?php

declare(strict_types=1);

namespace Kongono\CacheAdapterContract\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\CacheAdapterContract\Diagnostic\CacheAdapterContractDiagnostic;
use Throwable;

/** Reports one bounded adapter protocol relation failure. */
#[Api(ApiStatus::Experimental)]
final class InvalidCacheAdapterContractException extends CacheAdapterContractException
{
    /** @param array<string, string|int|bool> $context */
    public static function forDiagnostic(CacheAdapterContractDiagnostic $diagnostic, array $context, ?Throwable $previous = null): self
    {
        return new self($diagnostic, $context, $previous);
    }
}
