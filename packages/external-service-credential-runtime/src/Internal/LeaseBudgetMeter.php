<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceCredentialRuntime\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExternalServiceCredentialRuntime\Exception\InvalidCredentialLeaseRuntimeException;

/** Checks explicit monotonic observations against one inclusive lifetime ceiling. */
#[Api(ApiStatus::Internal)]
final class LeaseBudgetMeter
{
    private int $lastMilliseconds;
    private int $elapsedMilliseconds = 0;

    public function __construct(private readonly int $maximumLifetimeMilliseconds, private readonly int $initialMilliseconds)
    {
        if ($maximumLifetimeMilliseconds < 1) {
            throw InvalidCredentialLeaseRuntimeException::invalid('lifetime_exceeded');
        }
        if ($initialMilliseconds < 0) {
            throw InvalidCredentialLeaseRuntimeException::invalid('clock_negative');
        }
        $this->lastMilliseconds = $initialMilliseconds;
    }

    public function observe(int $milliseconds): void
    {
        if ($milliseconds < 0) {
            throw InvalidCredentialLeaseRuntimeException::invalid('clock_negative');
        }
        if ($milliseconds < $this->lastMilliseconds || $milliseconds < $this->initialMilliseconds) {
            throw InvalidCredentialLeaseRuntimeException::invalid('clock_regression');
        }
        $this->lastMilliseconds = $milliseconds;
        $this->elapsedMilliseconds = $milliseconds - $this->initialMilliseconds;
        if ($this->elapsedMilliseconds > $this->maximumLifetimeMilliseconds) {
            throw InvalidCredentialLeaseRuntimeException::invalid('lifetime_exceeded');
        }
    }

    public function elapsedMilliseconds(): int
    {
        return $this->elapsedMilliseconds;
    }

    public function maximumLifetimeMilliseconds(): int
    {
        return $this->maximumLifetimeMilliseconds;
    }
}
