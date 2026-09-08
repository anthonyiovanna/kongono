<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressApplication\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressApplication\Diagnostic\NetworkEgressApplicationDiagnostic;
use Throwable;

/** Preserves trusted generated-PHP load failure without disclosing a path. */
#[Api(ApiStatus::Experimental)]
final class NetworkEgressWiringLoadException extends NetworkEgressApplicationException
{
    public static function failed(string $stage, ?Throwable $previous = null): self
    {
        $allowed = ['path', 'snapshot', 'validation', 'identity', 'collision', 'require', 'return', 'shape', 'reverify', 'start'];

        return new self(NetworkEgressApplicationDiagnostic::LoadFailed, ['stage' => in_array($stage, $allowed, true) ? $stage : 'unsupported_stage'], $previous);
    }
}
