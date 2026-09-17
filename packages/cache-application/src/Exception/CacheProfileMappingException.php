<?php

declare(strict_types=1);

namespace Kongono\CacheApplication\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\CacheApplication\Diagnostic\CacheApplicationDiagnostic;
use Throwable;

/** Reports a missing or mismatched explicit profile/codec mapping. */
#[Api(ApiStatus::Experimental)]
final class CacheProfileMappingException extends CacheApplicationException
{
    public static function mismatch(string $stage, ?Throwable $previous = null): self
    {
        return new self(CacheApplicationDiagnostic::ProfileCodecMismatch, $stage, $previous);
    }
}
