<?php

declare(strict_types=1);

namespace Kongono\Composition\Tests\Fixture;

final class ApplicationDependsOnOperation
{
    public function __construct(private readonly OperationDependency $dependency) {}

    public function dependency(): OperationDependency
    {
        return $this->dependency;
    }
}
