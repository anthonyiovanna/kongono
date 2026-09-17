<?php

declare(strict_types=1);

namespace Kongono\CacheApplication\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\CacheApplication\Diagnostic\CacheApplicationDiagnostic;
use Throwable;

/** Reports unsafe source evidence without disclosing paths or bytes. */
#[Api(ApiStatus::Experimental)]
final class UnsafeCacheSourceException extends CacheApplicationException
{
    public static function unsafe(string $stage, ?Throwable $previous = null): self
    {
        $allowed = ['missing', 'linked', 'hard_linked', 'nonordinary', 'unreadable', 'oversized', 'changed_during_read', 'invalid_bytes'];
        return new self(CacheApplicationDiagnostic::UnsafeSource, in_array($stage, $allowed, true) ? $stage : 'unsafe_source', $previous);
    }
}
