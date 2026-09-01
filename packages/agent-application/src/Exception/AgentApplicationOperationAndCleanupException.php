<?php

declare(strict_types=1);

namespace Kongono\AgentApplication\Exception;

use Kongono\AgentApplication\Diagnostic\AgentApplicationDiagnostic;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Throwable;

/** Retains the exact pre-runtime primary failure and every cleanup failure. */
#[Api(ApiStatus::Experimental)]
final class AgentApplicationOperationAndCleanupException extends AgentApplicationException
{
    /** @param non-empty-list<Throwable> $cleanupFailures */
    public function __construct(private readonly Throwable $primaryFailure, private readonly array $cleanupFailures)
    {
        parent::__construct(AgentApplicationDiagnostic::LifecycleFailed, ['stage' => 'operation_and_cleanup'], $primaryFailure);
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
