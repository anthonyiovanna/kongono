<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressRuntime\Execution;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressContract\Outcome\EgressOperationResult;
use Kongono\NetworkEgressRuntime\Exception\InvalidEgressRuntimeException;

/** Returns bounded aggregate terminal evidence without sensitive operation details. */
#[Api(ApiStatus::Experimental)]
final readonly class EgressRuntimeResult
{
    public function __construct(
        private EgressOperationResult $operationResult,
        private int $elapsedMilliseconds,
        private int $resolutionCandidateCount,
        private int $progressEmissionCount,
        private int $peakBufferedBytes,
        private int $finalRetainedEvidenceBytes,
    ) {
        if (
            $elapsedMilliseconds < 0 || $elapsedMilliseconds > 3_600_000
            || $resolutionCandidateCount < 0 || $resolutionCandidateCount > 64
            || $progressEmissionCount < 0 || $progressEmissionCount > 4_096
            || $peakBufferedBytes < 0 || $peakBufferedBytes > 16_777_216
            || $finalRetainedEvidenceBytes < 0 || $finalRetainedEvidenceBytes > 1_048_576
        ) {
            throw InvalidEgressRuntimeException::invalid('result_bounds');
        }
    }

    public function operationResult(): EgressOperationResult
    {
        return $this->operationResult;
    }
    public function elapsedMilliseconds(): int
    {
        return $this->elapsedMilliseconds;
    }
    public function resolutionCandidateCount(): int
    {
        return $this->resolutionCandidateCount;
    }
    public function progressEmissionCount(): int
    {
        return $this->progressEmissionCount;
    }
    public function peakBufferedBytes(): int
    {
        return $this->peakBufferedBytes;
    }
    public function finalRetainedEvidenceBytes(): int
    {
        return $this->finalRetainedEvidenceBytes;
    }
}
