<?php

declare(strict_types=1);

namespace Kongono\StorageAdapterContract\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\StorageAdapterContract\Diagnostic\StorageAdapterContractDiagnostic;
use Throwable;

/** Reports unexpected execution failure without native disclosure. */
#[Api(ApiStatus::Experimental)]
final class StorageAdapterExecutionException extends StorageAdapterContractException
{
    public static function impossibleState(?Throwable $previous = null): self
    {
        return new self(StorageAdapterContractDiagnostic::ExecutionFailure, ['violation' => 'impossible_state'], $previous);
    }
}
