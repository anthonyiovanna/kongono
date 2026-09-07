<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationApplication\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationApplication\Diagnostic\ModelInvocationApplicationDiagnostic;
use Throwable;

/** Preserves a pre-runtime request-construction failure without flattening it. */
#[Api(ApiStatus::Experimental)]
final class ModelInvocationApplicationOperationException extends ModelInvocationApplicationException
{
    public static function failed(string $stage, ?Throwable $previous = null): self
    {
        $allowed = ['request'];

        return new self(ModelInvocationApplicationDiagnostic::OperationFailed, ['stage' => in_array($stage, $allowed, true) ? $stage : 'unsupported_stage'], $previous);
    }
}
