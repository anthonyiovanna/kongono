<?php

declare(strict_types=1);

namespace Kongono\StorageApplication\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\StorageApplication\Diagnostic\StorageApplicationDiagnostic;
use Throwable;

/** Reports one bounded package-owned invariant failure. */
#[Api(ApiStatus::Experimental)]
final class StorageApplicationInvariantException extends StorageApplicationException
{
    public static function failed(string $stage, ?Throwable $previous = null): self
    {
        return new self(StorageApplicationDiagnostic::InternalInvariant, in_array($stage, ['reentrancy', 'operation_mismatch', 'plan_relation', 'summary_relation'], true) ? $stage : 'invariant', $previous);
    }
}
