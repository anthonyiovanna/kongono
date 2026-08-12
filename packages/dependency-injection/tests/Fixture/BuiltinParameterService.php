<?php

declare(strict_types=1);

namespace Kongono\DependencyInjection\Tests\Fixture;

final class BuiltinParameterService implements EmptyServiceInterface
{
    public function __construct(public readonly string $dependency) {}
}
