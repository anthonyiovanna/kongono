<?php

declare(strict_types=1);

namespace Kongono\CacheApplication\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\CacheApplication\Diagnostic\CacheApplicationDiagnostic;
use Throwable;

/** Reports one bounded package-owned invariant failure. */
#[Api(ApiStatus::Experimental)]
final class CacheApplicationInvariantException extends CacheApplicationException
{
    public static function failed(string $stage, ?Throwable $previous = null): self
    {
        return new self(CacheApplicationDiagnostic::InternalInvariant, $stage, $previous);
    }
}
