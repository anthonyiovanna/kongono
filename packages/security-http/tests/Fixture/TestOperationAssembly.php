<?php

declare(strict_types=1);

namespace Kongono\SecurityHttp\Tests\Fixture;

use Kongono\Composition\Exception\CompositionLifecycleException;
use Kongono\Composition\Runtime\GeneratedOperationBoundary;
use Throwable;

final class TestOperationAssembly implements GeneratedOperationBoundary
{
    public static int $closeCalls = 0;
    public static ?Throwable $cleanupFailure = null;
    private bool $closed = false;

    public function __construct(private readonly TestSecurityRoot $root) {}

    public function operationRoot1(): TestSecurityRoot
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
        if (null !== $primaryFailure && null !== self::$cleanupFailure) {
            throw new CompositionLifecycleException($primaryFailure, [self::$cleanupFailure], 'operation');
        }
        if (null !== $primaryFailure) {
            throw $primaryFailure;
        }
        if (null !== self::$cleanupFailure) {
            throw self::$cleanupFailure;
        }
    }

    public function isClosed(): bool
    {
        return $this->closed;
    }
}
