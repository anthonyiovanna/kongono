<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressCurl\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressCurl\Exception\InvalidCurlTransportProfileException;

/** Enforces one non-reentrant pull and one idempotent close transition. */
#[Api(ApiStatus::Internal)]
final class CurlSessionGuard
{
    private const int FRESH = 0;
    private const int RUNNING = 1;
    private const int DONE = 2;
    private const int CLOSED = 3;
    private int $state = self::FRESH;

    public function begin(): bool
    {
        if (self::RUNNING === $this->state) {
            throw InvalidCurlTransportProfileException::lifecycle('reentrancy');
        }
        if (self::DONE === $this->state || self::CLOSED === $this->state) {
            return false;
        }
        $this->state = self::RUNNING;

        return true;
    }

    public function finish(): void
    {
        if (self::RUNNING === $this->state) {
            $this->state = self::DONE;
        }
    }

    public function close(): bool
    {
        if (self::CLOSED === $this->state) {
            return false;
        }
        $this->state = self::CLOSED;

        return true;
    }
}
