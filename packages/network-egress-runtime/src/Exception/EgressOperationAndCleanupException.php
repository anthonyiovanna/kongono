<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressRuntime\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressRuntime\Diagnostic\NetworkEgressRuntimeDiagnostic;
use Kongono\NetworkEgressRuntime\Execution\EgressRuntimeResult;
use Throwable;

/** Preserves the primary and close failures as separately inspectable evidence. */
#[Api(ApiStatus::Experimental)]
final class EgressOperationAndCleanupException extends NetworkEgressRuntimeException
{
    /** @var non-empty-list<Throwable> */
    private readonly array $cleanupFailures;

    /** @param list<Throwable> $cleanupFailures */
    public function __construct(
        private readonly EgressRuntimeResult $result,
        private readonly Throwable $primaryFailure,
        array $cleanupFailures,
    ) {
        if ([] === $cleanupFailures) {
            throw InvalidEgressRuntimeException::invalid('cleanup_failures_empty');
        }
        $this->cleanupFailures = [...$cleanupFailures];
        parent::__construct(NetworkEgressRuntimeDiagnostic::CleanupFailed, ['outcome' => $result->operationResult()->receipt()->outcome()->value, 'count' => count($cleanupFailures)], $primaryFailure);
    }

    public function result(): EgressRuntimeResult
    {
        return $this->result;
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
