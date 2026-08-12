<?php

declare(strict_types=1);

namespace Kongono\DependencyInjection\Tests\Fixture;

final class LongCycleAService implements LongCycleAInterface
{
    public function __construct(public readonly LongCycleBInterface $dependency) {}
}
