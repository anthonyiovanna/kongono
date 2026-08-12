<?php

declare(strict_types=1);

namespace Kongono\DependencyInjection\Tests\Fixture;

final class CycleBService implements CycleBInterface
{
    public function __construct(public readonly CycleAInterface $dependency) {}
}
