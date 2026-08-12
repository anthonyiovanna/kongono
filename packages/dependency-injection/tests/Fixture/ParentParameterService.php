<?php

declare(strict_types=1);

namespace Kongono\DependencyInjection\Tests\Fixture;

final class ParentParameterService extends ParentParameterBase
{
    public function __construct(public readonly parent $dependency) {}
}
