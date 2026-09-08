<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressApplication\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressApplication\Diagnostic\NetworkEgressApplicationDiagnostic;
use Throwable;

/** Reports finite lifecycle conflicts before another resolver or transport effect. */
#[Api(ApiStatus::Experimental)]
final class NetworkEgressApplicationLifecycleException extends NetworkEgressApplicationException
{
    public static function failed(string $stage, ?Throwable $previous = null): self
    {
        $allowed = ['transport_capabilities', 'transport_capability', 'operation_active', 'application_retired'];

        return new self(NetworkEgressApplicationDiagnostic::LifecycleFailed, ['stage' => in_array($stage, $allowed, true) ? $stage : 'unsupported_stage'], $previous);
    }
}
