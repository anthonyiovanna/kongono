<?php

declare(strict_types=1);

namespace Kongono\AgentRuntime\Exception;

use Kongono\AgentRuntime\Diagnostic\AgentRuntimeDiagnostic;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Throwable;

/** Retains the exact primary failure and every exact cleanup failure. */
#[Api(ApiStatus::Experimental)]
final class AgentRuntimeOperationAndCleanupException extends AgentRuntimeException
{
    /** @param non-empty-list<Throwable> $cleanupFailures */
    public function __construct(
        private readonly Throwable $primaryFailure,
        private readonly array $cleanupFailures,
    ) {
        parent::__construct(
            AgentRuntimeDiagnostic::OperationAndCleanupFailed,
            ['primary_present' => true, 'cleanup_count' => count($cleanupFailures)],
            $primaryFailure,
        );
    }

    public function primaryFailure(): Throwable
    {
        return $this->primaryFailure;
    }
    /** @return non-empty-list<Throwable> */
    public function cleanupFailures(): array
    {
        return [...$this->cleanupFailures];
    }
}
