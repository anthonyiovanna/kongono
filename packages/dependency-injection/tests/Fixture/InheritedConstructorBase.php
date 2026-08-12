<?php

declare(strict_types=1);

namespace Kongono\DependencyInjection\Tests\Fixture;

abstract readonly class InheritedConstructorBase implements EmptyServiceInterface
{
    public function __construct(public FirstDependencyInterface $dependency) {}
}
