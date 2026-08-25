<?php

declare(strict_types=1);

namespace Kongono\DatabaseApplication\Tests\Fixture;

use Kongono\Composition\Runtime\GeneratedApplicationBoundary;
use Kongono\Composition\Runtime\GeneratedOperationBoundary;
use Throwable;

final class InvalidApplicationRootAssembly implements GeneratedApplicationBoundary
{
    public static int $startCalls = 0;

    public static function start(TestInput $input): self
    {
        unset($input);
        ++self::$startCalls;

        return new self();
    }

    public function applicationRoot1(): TestRoot
    {
        return new TestRoot(1);
    }

    public function beginOperation(): GeneratedOperationBoundary
    {
        return new TestOperationAssembly(new TestRoot(1));
    }

    public function shutdown(?Throwable $primaryFailure = null): void
    {
        if (null !== $primaryFailure) {
            throw $primaryFailure;
        }
    }

    public function isRetired(): bool
    {
        return false;
    }
}
