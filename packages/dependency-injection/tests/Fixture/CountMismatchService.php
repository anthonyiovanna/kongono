<?php

declare(strict_types=1);

namespace Kongono\DependencyInjection\Tests\Fixture;

final class CountMismatchService implements EmptyServiceInterface
{
    public function __construct(public readonly FirstDependencyInterface $dependency) {}
}
