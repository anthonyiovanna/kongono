<?php

declare(strict_types=1);

namespace Kongono\AssetApplication\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetApplication\Diagnostic\AssetApplicationDiagnostic;
use Throwable;

/** Reports a supplied neutral adapter failure without foreign disclosure. */
#[Api(ApiStatus::Internal)]
final class AssetAdapterOperationException extends AssetApplicationException
{
    public static function failed(string $stage, ?Throwable $previous = null): self
    {
        return new self(AssetApplicationDiagnostic::AdapterFailed, in_array($stage, ['execution', 'request_relation', 'result_validation'], true) ? $stage : 'adapter', $previous);
    }
}
