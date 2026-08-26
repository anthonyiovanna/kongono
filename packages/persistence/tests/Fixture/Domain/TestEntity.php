<?php

declare(strict_types=1);

namespace Kongono\Persistence\Tests\Fixture\Domain;

final class TestEntity
{
    public int $unrelatedMethodCalls = 0;

    public function __construct(
        public int $id,
        public string $name,
        public int $version,
    ) {}

    public function unrelatedMethod(): void
    {
        ++$this->unrelatedMethodCalls;
    }
}
