<?php

declare(strict_types=1);

namespace Kongono\AgentApplication\Exception;

use Kongono\AgentApplication\Diagnostic\AgentApplicationDiagnostic;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Throwable;

/** Preserves one trusted generated-PHP load failure without exposing its path. */
#[Api(ApiStatus::Experimental)]
final class AgentWiringLoadException extends AgentApplicationException
{
    public static function failed(string $stage, ?Throwable $previous = null): self
    {
        $allowed = ['path', 'snapshot', 'validation', 'identity', 'collision', 'require', 'shape', 'start'];

        return new self(AgentApplicationDiagnostic::LoadFailed, ['stage' => in_array($stage, $allowed, true) ? $stage : 'unsupported_stage'], $previous);
    }
}
