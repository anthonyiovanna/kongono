<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressContract\Budget;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressContract\Exception\InvalidNetworkEgressContractException;

/** Stores finite declared limits without metering a live operation. */
#[Api(ApiStatus::Experimental)]
final readonly class EgressBudget
{
    public function __construct(
        private int $maximumConnectionAttempts,
        private int $maximumRedirects,
        private int $maximumResolutionCandidates,
        private int $maximumRequestBytes,
        private int $maximumResponseBytes,
        private int $maximumHeaderCount,
        private int $maximumAggregateHeaderBytes,
        private int $maximumBufferedBytes,
        private int $maximumRetainedEvidenceBytes,
        private int $connectTimeoutMilliseconds,
        private int $firstByteTimeoutMilliseconds,
        private int $totalTimeoutMilliseconds,
    ) {
        self::bounded($maximumConnectionAttempts, 1, 16, 'connection_attempts_range', 'connection_attempts');
        self::bounded($maximumRedirects, 0, 8, 'redirects_range', 'redirects');
        self::bounded($maximumResolutionCandidates, 1, 64, 'resolution_candidates_range', 'resolution_candidates');
        self::bounded($maximumRequestBytes, 0, 1073741824, 'request_bytes_range', 'request_bytes');
        self::bounded($maximumResponseBytes, 0, 1073741824, 'response_bytes_range', 'response_bytes');
        self::bounded($maximumHeaderCount, 1, 256, 'header_count_range', 'header_count');
        self::bounded($maximumAggregateHeaderBytes, 1, 1048576, 'header_bytes_range', 'header_bytes');
        self::bounded($maximumBufferedBytes, 1, 16777216, 'buffered_bytes_range', 'buffered_bytes');
        self::bounded($maximumRetainedEvidenceBytes, 1, 1048576, 'evidence_bytes_range', 'evidence_bytes');
        self::bounded($connectTimeoutMilliseconds, 1, 300000, 'connect_timeout_range', 'connect_timeout_milliseconds');
        self::bounded($firstByteTimeoutMilliseconds, 1, 300000, 'first_byte_timeout_range', 'first_byte_timeout_milliseconds');
        self::bounded($totalTimeoutMilliseconds, 1, 3600000, 'total_timeout_range', 'total_timeout_milliseconds');
        if ($connectTimeoutMilliseconds > $totalTimeoutMilliseconds) {
            throw InvalidNetworkEgressContractException::invalidBudget('connect_timeout_exceeds_total', $totalTimeoutMilliseconds);
        }
        if ($firstByteTimeoutMilliseconds > $totalTimeoutMilliseconds) {
            throw InvalidNetworkEgressContractException::invalidBudget('first_byte_timeout_exceeds_total', $totalTimeoutMilliseconds);
        }
    }

    public function maximumConnectionAttempts(): int
    {
        return $this->maximumConnectionAttempts;
    }

    public function maximumRedirects(): int
    {
        return $this->maximumRedirects;
    }

    public function maximumResolutionCandidates(): int
    {
        return $this->maximumResolutionCandidates;
    }

    public function maximumRequestBytes(): int
    {
        return $this->maximumRequestBytes;
    }

    public function maximumResponseBytes(): int
    {
        return $this->maximumResponseBytes;
    }

    public function maximumHeaderCount(): int
    {
        return $this->maximumHeaderCount;
    }

    public function maximumAggregateHeaderBytes(): int
    {
        return $this->maximumAggregateHeaderBytes;
    }

    public function maximumBufferedBytes(): int
    {
        return $this->maximumBufferedBytes;
    }

    public function maximumRetainedEvidenceBytes(): int
    {
        return $this->maximumRetainedEvidenceBytes;
    }

    public function connectTimeoutMilliseconds(): int
    {
        return $this->connectTimeoutMilliseconds;
    }

    public function firstByteTimeoutMilliseconds(): int
    {
        return $this->firstByteTimeoutMilliseconds;
    }

    public function totalTimeoutMilliseconds(): int
    {
        return $this->totalTimeoutMilliseconds;
    }

    public function equals(self $other): bool
    {
        return $this->maximumConnectionAttempts === $other->maximumConnectionAttempts
            && $this->maximumRedirects === $other->maximumRedirects
            && $this->maximumResolutionCandidates === $other->maximumResolutionCandidates
            && $this->maximumRequestBytes === $other->maximumRequestBytes
            && $this->maximumResponseBytes === $other->maximumResponseBytes
            && $this->maximumHeaderCount === $other->maximumHeaderCount
            && $this->maximumAggregateHeaderBytes === $other->maximumAggregateHeaderBytes
            && $this->maximumBufferedBytes === $other->maximumBufferedBytes
            && $this->maximumRetainedEvidenceBytes === $other->maximumRetainedEvidenceBytes
            && $this->connectTimeoutMilliseconds === $other->connectTimeoutMilliseconds
            && $this->firstByteTimeoutMilliseconds === $other->firstByteTimeoutMilliseconds
            && $this->totalTimeoutMilliseconds === $other->totalTimeoutMilliseconds;
    }

    private static function bounded(int $value, int $minimum, int $maximum, string $rangeViolation, string $limitViolation): void
    {
        if ($value > $maximum) {
            throw InvalidNetworkEgressContractException::contractLimitExceeded($limitViolation, $maximum);
        }
        if ($value < $minimum) {
            throw InvalidNetworkEgressContractException::invalidBudget($rangeViolation, $minimum);
        }
    }
}
