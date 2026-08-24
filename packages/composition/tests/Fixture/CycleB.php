<?php

declare(strict_types=1);

namespace Kongono\Composition\Tests\Fixture;

final class CycleB
{
    public function __construct(private readonly CycleA $dependency) {}

    public function dependency(): CycleA
    {
        return $this->dependency;
    }
}
