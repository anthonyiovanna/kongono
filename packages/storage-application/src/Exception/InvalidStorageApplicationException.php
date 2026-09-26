<?php

declare(strict_types=1);

namespace Kongono\StorageApplication\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\StorageApplication\Diagnostic\StorageApplicationDiagnostic;
use Throwable;

/** Reports a closed definition violation without disclosing rejected values. */
#[Api(ApiStatus::Experimental)]
final class InvalidStorageApplicationException extends StorageApplicationException
{
    public static function invalid(string $stage, ?Throwable $previous = null): self
    {
        $allowed = ['invalid_path', 'invalid_root', 'invalid_source_file', 'invalid_output', 'outside_root', 'invalid_json', 'invalid_schema', 'invalid_profile', 'duplicate_profile', 'invalid_capability'];
        return new self(StorageApplicationDiagnostic::InvalidDefinition, in_array($stage, $allowed, true) ? $stage : 'invalid_definition', $previous);
    }
}
