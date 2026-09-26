<?php

declare(strict_types=1);

namespace Kongono\StorageAdapterContract\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\StorageAdapterContract\Diagnostic\StorageAdapterContractDiagnostic;
use Throwable;

/** Reports one bounded adapter protocol relation failure. */
#[Api(ApiStatus::Experimental)]
final class InvalidStorageAdapterContractException extends StorageAdapterContractException
{
    /** @param array<string, string|int|bool> $context */
    public static function forDiagnostic(StorageAdapterContractDiagnostic $diagnostic, array $context, ?Throwable $previous = null): self
    {
        return new self($diagnostic, $context, $previous);
    }
}
