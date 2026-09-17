<?php

declare(strict_types=1);

namespace Kongono\CacheAdapterContract\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\CacheAdapterContract\Diagnostic\CacheAdapterContractDiagnostic;
use Throwable;

/** Reports execution failure without exposing provider-native material. */
#[Api(ApiStatus::Experimental)]
final class CacheAdapterExecutionException extends CacheAdapterContractException
{
    public static function impossibleState(?Throwable $previous = null): self
    {
        return new self(CacheAdapterContractDiagnostic::ExecutionFailure, ['violation' => 'impossible_state'], $previous);
    }
}
