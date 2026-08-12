<?php

declare(strict_types=1);

namespace Kongono\DependencyInjection\Tests\Fixture;

final class UntypedParameterService implements EmptyServiceInterface
{
    /** @var mixed */
    public readonly mixed $dependency;

    /** @param mixed $dependency */
    public function __construct($dependency)
    {
        $this->dependency = $dependency;
    }
}
