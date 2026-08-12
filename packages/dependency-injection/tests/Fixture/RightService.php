<?php

declare(strict_types=1);

namespace Kongono\DependencyInjection\Tests\Fixture;

final readonly class RightService implements RightServiceInterface
{
    public function __construct(public FirstDependencyInterface $dependency) {}
}
