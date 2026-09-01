<?php

declare(strict_types=1);

namespace Kongono\AgentRuntime\Exception;

use Kongono\AgentRuntime\Diagnostic\AgentRuntimeDiagnostic;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Throwable;

/** Wraps a caller-owned collaborator failure without disclosing its values. */
#[Api(ApiStatus::Experimental)]
final class AgentRuntimeOperationException extends AgentRuntimeException
{
    private const array STAGES = ['clock', 'authority', 'cancellation', 'proposal', 'capability'];

    public static function failed(string $stage, ?Throwable $previous = null): self
    {
        return new self(
            AgentRuntimeDiagnostic::OperationFailed,
            ['stage' => in_array($stage, self::STAGES, true) ? $stage : 'unsupported_stage'],
            $previous,
        );
    }
}
