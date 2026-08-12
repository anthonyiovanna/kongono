<?php

declare(strict_types=1);

namespace Kongono\DependencyInjection\Tests\Fixture;

final class ByReferenceParameterService implements EmptyServiceInterface
{
    public readonly FirstDependencyInterface $dependency;

    public function __construct(FirstDependencyInterface &$dependency)
    {
        $this->dependency = $dependency;
    }
}
