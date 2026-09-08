<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressRuntime\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressRuntime\Diagnostic\NetworkEgressRuntimeDiagnostic;
use Kongono\NetworkEgressRuntime\Execution\EgressRuntimeResult;
use Throwable;

/** Retains every exact close failure after an otherwise terminal operation. */
#[Api(ApiStatus::Experimental)]
final class EgressCleanupException extends NetworkEgressRuntimeException
{
    /** @var non-empty-list<Throwable> */
    private readonly array $cleanupFailures;

    /** @param list<Throwable> $cleanupFailures */
    public function __construct(private readonly EgressRuntimeResult $result, array $cleanupFailures)
    {
        if ([] === $cleanupFailures) {
            throw InvalidEgressRuntimeException::invalid('cleanup_failures_empty');
        }
        $this->cleanupFailures = [...$cleanupFailures];
        parent::__construct(NetworkEgressRuntimeDiagnostic::CleanupFailed, ['outcome' => $result->operationResult()->receipt()->outcome()->value, 'count' => count($cleanupFailures)], $cleanupFailures[0]);
    }

    public function result(): EgressRuntimeResult
    {
        return $this->result;
    }

    /** @return non-empty-list<Throwable> */
    public function cleanupFailures(): array
    {
        return [...$this->cleanupFailures];
    }
}
