<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationApplication\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationApplication\Diagnostic\ModelInvocationApplicationDiagnostic;
use Throwable;

/** Reports finite lifecycle conflicts before another adapter effect. */
#[Api(ApiStatus::Experimental)]
final class ModelInvocationApplicationLifecycleException extends ModelInvocationApplicationException
{
    public static function failed(string $stage, ?Throwable $previous = null): self
    {
        $allowed = ['adapter_identity', 'operation_active', 'application_retired'];

        return new self(ModelInvocationApplicationDiagnostic::LifecycleFailed, ['stage' => in_array($stage, $allowed, true) ? $stage : 'unsupported_stage'], $previous);
    }
}
