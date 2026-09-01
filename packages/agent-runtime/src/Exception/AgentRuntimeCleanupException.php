<?php

declare(strict_types=1);

namespace Kongono\AgentRuntime\Exception;

use Kongono\AgentRuntime\Diagnostic\AgentRuntimeDiagnostic;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Throwable;

/** Retains every cleanup failure in exact observed reverse-close order. */
#[Api(ApiStatus::Experimental)]
final class AgentRuntimeCleanupException extends AgentRuntimeException
{
    /** @param non-empty-list<Throwable> $cleanupFailures */
    public function __construct(private readonly array $cleanupFailures)
    {
        parent::__construct(AgentRuntimeDiagnostic::CleanupFailed, ['cleanup_count' => count($cleanupFailures)], $cleanupFailures[0]);
    }

    /** @return non-empty-list<Throwable> */
    public function cleanupFailures(): array
    {
        return [...$this->cleanupFailures];
    }
}
