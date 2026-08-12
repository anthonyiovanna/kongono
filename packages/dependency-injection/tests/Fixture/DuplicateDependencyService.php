<?php

declare(strict_types=1);

namespace Kongono\DependencyInjection\Tests\Fixture;

final readonly class DuplicateDependencyService implements DuplicateDependencyServiceInterface
{
    public function __construct(
        public FirstDependencyInterface $first,
        public FirstDependencyInterface $second,
    ) {}
}
