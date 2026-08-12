<?php

declare(strict_types=1);

namespace Kongono\DependencyInjection\Tests\Fixture;

final class OtherSelfCycleService implements OtherSelfCycleInterface
{
    public function __construct(public readonly OtherSelfCycleInterface $dependency) {}
}
