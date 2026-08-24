<?php

declare(strict_types=1);

namespace Kongono\Composition\Tests\Fixture;

final class HandlerA implements Handler
{
    public function __construct(private readonly Logger $logger) {}

    public function logger(): Logger
    {
        return $this->logger;
    }
}
