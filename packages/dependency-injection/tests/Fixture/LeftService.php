<?php

declare(strict_types=1);

namespace Kongono\DependencyInjection\Tests\Fixture;

final readonly class LeftService implements LeftServiceInterface
{
    public function __construct(public FirstDependencyInterface $dependency) {}
}
