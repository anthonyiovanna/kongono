<?php

declare(strict_types=1);

namespace Kongono\DependencyInjection\Tests\Fixture;

final class CycleAService implements CycleAInterface
{
    public function __construct(public readonly CycleBInterface $dependency) {}
}
