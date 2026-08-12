<?php

declare(strict_types=1);

namespace Kongono\DependencyInjection\Tests\Fixture;

final class LongCycleCService implements LongCycleCInterface
{
    public function __construct(public readonly LongCycleAInterface $dependency) {}
}
