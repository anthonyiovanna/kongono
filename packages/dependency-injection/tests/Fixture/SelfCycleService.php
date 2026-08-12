<?php

declare(strict_types=1);

namespace Kongono\DependencyInjection\Tests\Fixture;

final class SelfCycleService implements SelfCycleInterface
{
    public function __construct(public readonly SelfCycleInterface $dependency) {}
}
