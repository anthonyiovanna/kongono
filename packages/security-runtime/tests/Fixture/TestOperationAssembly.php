<?php

declare(strict_types=1);

namespace Kongono\SecurityRuntime\Tests\Fixture;

use Kongono\Composition\Exception\CompositionLifecycleException;
use Kongono\Composition\Runtime\GeneratedOperationBoundary;
use Throwable;

final class TestOperationAssembly implements GeneratedOperationBoundary
{
    public static int $closeCalls = 0;
    public static bool $failRootAccess = false;
    public static ?Throwable $cleanupFailure = null;
    public static ?Throwable $lastPrimaryFailure = null;
    private bool $closed = false;

    public function __construct(private readonly TestSecurityRoot $root) {}

    public function operationRoot1(): TestSecurityRoot
    {
        if (self::$failRootAccess) {
            throw new \RuntimeException('adversarial root access failure');
        }

        return $this->root;
    }

    public function close(?Throwable $primaryFailure = null): void
    {
        if ($this->closed) {
            return;
        }
        $this->closed = true;
        ++self::$closeCalls;
        self::$lastPrimaryFailure = $primaryFailure;
        TestApplicationAssembly::operationClosed();
        $cleanupFailure = self::$cleanupFailure;
        if (null !== $primaryFailure && null !== $cleanupFailure) {
            throw new CompositionLifecycleException($primaryFailure, [$cleanupFailure], 'operation');
        }
        if (null !== $primaryFailure) {
            throw $primaryFailure;
        }
        if (null !== $cleanupFailure) {
            throw $cleanupFailure;
        }
    }

    public function isClosed(): bool
    {
        return $this->closed;
    }
}
