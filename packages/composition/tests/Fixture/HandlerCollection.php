<?php

declare(strict_types=1);

namespace Kongono\Composition\Tests\Fixture;

final class HandlerCollection implements Handler
{
    public function __construct(
        private readonly HandlerA $first,
        private readonly HandlerB $second,
    ) {}

    public function first(): HandlerA
    {
        return $this->first;
    }

    public function second(): HandlerB
    {
        return $this->second;
    }
}
