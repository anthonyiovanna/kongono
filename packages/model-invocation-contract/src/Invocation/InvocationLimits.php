<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationContract\Invocation;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationContract\Exception\InvalidModelInvocationContractException;

/** Declares inert request bounds without metering or runtime enforcement. */
#[Api(ApiStatus::Experimental)]
final readonly class InvocationLimits
{
    public function __construct(
        private int $maxOutputBytes,
        private int $maxStreamEvents,
        private int $maxUsageEntries,
    ) {
        if ($maxOutputBytes < 1 || $maxOutputBytes > 1048576 || $maxStreamEvents < 1 || $maxStreamEvents > 4096 || $maxUsageEntries < 0 || $maxUsageEntries > 16) {
            throw InvalidModelInvocationContractException::invalidRequest('invocation_limit_range');
        }
    }

    public function maxOutputBytes(): int
    {
        return $this->maxOutputBytes;
    }

    public function maxStreamEvents(): int
    {
        return $this->maxStreamEvents;
    }

    public function maxUsageEntries(): int
    {
        return $this->maxUsageEntries;
    }

    public function equals(self $other): bool
    {
        return $this->maxOutputBytes === $other->maxOutputBytes
            && $this->maxStreamEvents === $other->maxStreamEvents
            && $this->maxUsageEntries === $other->maxUsageEntries;
    }
}
