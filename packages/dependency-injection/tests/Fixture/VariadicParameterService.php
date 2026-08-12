<?php

declare(strict_types=1);

namespace Kongono\DependencyInjection\Tests\Fixture;

final class VariadicParameterService implements EmptyServiceInterface
{
    /** @var list<FirstDependencyInterface> */
    public readonly array $dependencies;

    public function __construct(FirstDependencyInterface ...$dependencies)
    {
        $this->dependencies = array_values($dependencies);
    }
}
