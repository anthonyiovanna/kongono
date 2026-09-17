<?php

declare(strict_types=1);

namespace Kongono\CacheApplication\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\CacheApplication\Diagnostic\CacheApplicationDiagnostic;
use Throwable;

/** Reports a closed definition violation without disclosing rejected values. */
#[Api(ApiStatus::Experimental)]
final class InvalidCacheApplicationException extends CacheApplicationException
{
    public static function invalid(string $stage, ?Throwable $previous = null): self
    {
        $allowed = ['invalid_path', 'invalid_root', 'invalid_source_file', 'invalid_output', 'outside_root', 'invalid_json', 'invalid_schema', 'invalid_profile'];
        return new self(CacheApplicationDiagnostic::InvalidDefinition, in_array($stage, $allowed, true) ? $stage : 'invalid_definition', $previous);
    }
}
