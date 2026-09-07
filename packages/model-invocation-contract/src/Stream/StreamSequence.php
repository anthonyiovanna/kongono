<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationContract\Stream;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationContract\Exception\InvalidModelInvocationContractException;

/** Carries one bounded event position without enforcing stream continuity. */
#[Api(ApiStatus::Experimental)]
final readonly class StreamSequence
{
    public function __construct(private int $value)
    {
        if ($value < 1 || $value > 4096) {
            throw InvalidModelInvocationContractException::invalidStreamEvent('stream_sequence_range');
        }
    }

    public function value(): int
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
