<?php

declare(strict_types=1);

namespace Kongono\Composition\Tests\Fixture;

final class CycleA
{
    public function __construct(private readonly CycleB $dependency) {}

    public function dependency(): CycleB
    {
        return $this->dependency;
    }
}
