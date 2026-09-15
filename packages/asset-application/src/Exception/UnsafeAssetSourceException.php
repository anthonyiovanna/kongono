<?php

declare(strict_types=1);

namespace Kongono\AssetApplication\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetApplication\Diagnostic\AssetApplicationDiagnostic;
use Throwable;

/** Reports an unsafe source read through one closed nondisclosing token. */
#[Api(ApiStatus::Internal)]
final class UnsafeAssetSourceException extends AssetApplicationException
{
    public static function unsafe(string $violation, ?Throwable $previous = null): self
    {
        $allowed = ['missing', 'linked', 'hard_linked', 'nonordinary', 'unreadable', 'oversized', 'changed_during_read', 'invalid_bytes'];

        return new self(AssetApplicationDiagnostic::UnsafeSource, in_array($violation, $allowed, true) ? $violation : 'unsafe_source', $previous);
    }
}
