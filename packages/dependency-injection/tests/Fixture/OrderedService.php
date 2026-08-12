<?php

declare(strict_types=1);

namespace Kongono\DependencyInjection\Tests\Fixture;

final readonly class OrderedService implements OrderedServiceInterface
{
    public function __construct(
        public FirstDependencyInterface $first,
        public SecondDependencyInterface $second,
    ) {}
}
