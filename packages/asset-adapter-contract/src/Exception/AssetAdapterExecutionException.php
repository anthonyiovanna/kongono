<?php

declare(strict_types=1);

namespace Kongono\AssetAdapterContract\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetAdapterContract\Diagnostic\AssetAdapterContractDiagnostic;
use Kongono\AssetAdapterContract\Internal\AdapterContractGuard;
use Throwable;

/** Reports one terminal nondisclosing adapter execution failure. */
#[Api(ApiStatus::Experimental)]
final class AssetAdapterExecutionException extends AssetAdapterContractException
{
    private const array VIOLATIONS = ['adapter_failed','process_failed','invalid_result'];
    public static function failed(string $violation, ?Throwable $previous = null): self
    {
        return new self(AssetAdapterContractDiagnostic::ExecutionFailed, ['violation' => AdapterContractGuard::token($violation, self::VIOLATIONS)], $previous);
    }
}
