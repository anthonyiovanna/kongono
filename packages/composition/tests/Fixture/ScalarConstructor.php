<?php

declare(strict_types=1);

namespace Kongono\Composition\Tests\Fixture;

final class ScalarConstructor
{
    public function __construct(private readonly string $value) {}

    public function value(): string
    {
        return $this->value;
    }
}
