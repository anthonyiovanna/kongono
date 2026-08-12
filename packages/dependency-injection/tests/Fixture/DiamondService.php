<?php

declare(strict_types=1);

namespace Kongono\DependencyInjection\Tests\Fixture;

final readonly class DiamondService implements DiamondServiceInterface
{
    public function __construct(
        public LeftServiceInterface $left,
        public RightServiceInterface $right,
    ) {}
}
