<?php

declare(strict_types=1);

namespace Kongono\ViewApplication\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ViewApplication\Diagnostic\ViewApplicationDiagnostic;
use Throwable;

/** Reports unsafe or unstable source evidence through closed tokens. */
#[Api(ApiStatus::Internal)]
final class UnsafeViewSourceException extends ViewApplicationException
{
    public static function unsafe(string $violation, ?Throwable $previous = null): self
    {
        $allowed = ['missing', 'nonordinary', 'linked', 'hard_linked', 'unreadable', 'oversized', 'changed_during_read', 'invalid_bytes'];

        return new self(ViewApplicationDiagnostic::UnsafeSource, in_array($violation, $allowed, true) ? $violation : 'unsafe_source', $previous);
    }
}
