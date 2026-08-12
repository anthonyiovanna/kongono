<?php

declare(strict_types=1);

namespace Kongono\DependencyInjection\Tests\Fixture;

final class LongCycleBService implements LongCycleBInterface
{
    public function __construct(public readonly LongCycleCInterface $dependency) {}
}
