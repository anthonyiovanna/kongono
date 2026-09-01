<?php

declare(strict_types=1);

namespace Kongono\AgentApplication\Exception;

use Kongono\AgentApplication\Diagnostic\AgentApplicationDiagnostic;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Throwable;

/** Retains every pre-runtime cleanup failure in observed reverse-close order. */
#[Api(ApiStatus::Experimental)]
final class AgentApplicationCleanupException extends AgentApplicationException
{
    /** @param non-empty-list<Throwable> $cleanupFailures */
    public function __construct(private readonly array $cleanupFailures)
    {
        parent::__construct(AgentApplicationDiagnostic::LifecycleFailed, ['stage' => 'cleanup'], $cleanupFailures[0]);
    }

    /** @return non-empty-list<Throwable> */
    public function cleanupFailures(): array
    {
        return [...$this->cleanupFailures];
    }
}
