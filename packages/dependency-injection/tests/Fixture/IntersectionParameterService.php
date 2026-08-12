<?php

declare(strict_types=1);

namespace Kongono\DependencyInjection\Tests\Fixture;

final class IntersectionParameterService implements EmptyServiceInterface
{
    public function __construct(public readonly FirstDependencyInterface&SecondDependencyInterface $dependency) {}
}
