<?php

declare(strict_types=1);

namespace Kongono\EventRuntime\Tests\Fixture;

use Kongono\EventContract\ApplicationEvent;

class FirstEvent implements ApplicationEvent
{
    public function __construct(public readonly string $secret = '') {}

    public function __toString(): string
    {
        throw new \LogicException('Event must not be stringified.');
    }

    /** @return array<never, never> */
    public function __serialize(): array
    {
        throw new \LogicException('Event must not be serialized.');
    }
}
