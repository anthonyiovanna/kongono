<?php

declare(strict_types=1);

namespace Kongono\AssetApplication\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetApplication\Diagnostic\AssetApplicationDiagnostic;
use Throwable;

/** Reports a closed definition violation without disclosing rejected values. */
#[Api(ApiStatus::Experimental)]
final class InvalidAssetApplicationException extends AssetApplicationException
{
    public static function invalid(string $violation, ?Throwable $previous = null): self
    {
        $allowed = ['empty_value', 'nul_byte', 'invalid_root', 'invalid_source_file', 'invalid_output', 'outside_root', 'invalid_json', 'invalid_schema', 'invalid_declaration', 'invalid_dependency', 'invalid_entry_point'];

        return new self(AssetApplicationDiagnostic::InvalidDefinition, in_array($violation, $allowed, true) ? $violation : 'invalid_definition', $previous);
    }
}
