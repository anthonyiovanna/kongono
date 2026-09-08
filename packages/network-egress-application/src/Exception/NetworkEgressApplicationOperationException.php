<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressApplication\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressApplication\Diagnostic\NetworkEgressApplicationDiagnostic;
use Throwable;

/** Preserves request, runtime, and invariant failure identity without disclosure. */
#[Api(ApiStatus::Experimental)]
final class NetworkEgressApplicationOperationException extends NetworkEgressApplicationException
{
    public static function request(Throwable $previous): self
    {
        return new self(NetworkEgressApplicationDiagnostic::RuntimeRequestFailed, ['stage' => 'request'], $previous);
    }

    public static function operation(Throwable $previous): self
    {
        return new self(NetworkEgressApplicationDiagnostic::OperationFailed, ['stage' => 'runtime'], $previous);
    }

    public static function invariant(): self
    {
        return new self(NetworkEgressApplicationDiagnostic::InternalInvariantFailed, ['stage' => 'collaborator_state']);
    }
}
