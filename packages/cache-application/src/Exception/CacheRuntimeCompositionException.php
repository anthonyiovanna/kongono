<?php

declare(strict_types=1);

namespace Kongono\CacheApplication\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\CacheApplication\Diagnostic\CacheApplicationDiagnostic;
use Throwable;

/** Reports exact-current runtime composition failure. */
#[Api(ApiStatus::Experimental)]
final class CacheRuntimeCompositionException extends CacheApplicationException
{
    public static function failed(?Throwable $previous = null): self
    {
        return new self(CacheApplicationDiagnostic::RuntimeCompositionFailed, 'composition', $previous);
    }
}
