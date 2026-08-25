<?php

declare(strict_types=1);

namespace Kongono\DatabaseApplication\Tests\Fixture;

use Kongono\Composition\Runtime\GeneratedOperationBoundary;
use Throwable;

final class TestOperationAssembly implements GeneratedOperationBoundary
{
    public static int $closeCalls = 0;
    private bool $closed = false;

    public function __construct(private readonly TestRoot $root) {}

    public function operationRoot1(): TestRoot
    {
        return $this->root;
    }

    public function close(?Throwable $primaryFailure = null): void
    {
        if ($this->closed) {
            return;
        }
        $this->closed = true;
        ++self::$closeCalls;
        TestApplicationAssembly::operationClosed();
        if (null !== $primaryFailure) {
            throw $primaryFailure;
        }
    }

    public function isClosed(): bool
    {
        return $this->closed;
    }
}
