<?php

declare(strict_types=1);

namespace Kongono\DependencyInjection\Tests\Fixture;

final class WrongParameterService implements EmptyServiceInterface
{
    public function __construct(public readonly SecondDependencyInterface $dependency) {}
}
