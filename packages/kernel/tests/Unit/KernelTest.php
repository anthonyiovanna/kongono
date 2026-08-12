<?php

declare(strict_types=1);

namespace Kongono\Kernel\Tests\Unit;

use Kongono\Kernel\Exception\ExceptionInterface;
use Kongono\Kernel\Exception\LifecycleException;
use Kongono\Kernel\Kernel;
use Kongono\Kernel\Lifecycle\KernelState;
use PHPUnit\Framework\TestCase;
use Throwable;

final class KernelTest extends TestCase
{
    public function testNewKernelIsUnbootedWithNoActiveOperations(): void
    {
        $kernel = new Kernel();

        self::assertSame(KernelState::Unbooted, $kernel->state());
        self::assertSame(0, $kernel->activeOperationCount());
    }

    public function testKernelBootsOnce(): void
    {
        $kernel = new Kernel();

        $kernel->boot();

        self::assertSame(KernelState::Booted, $kernel->state());
        self::assertSame(0, $kernel->activeOperationCount());
    }

    public function testKernelCannotBootTwiceWithoutStateMutation(): void
    {
        $kernel = new Kernel();
        $kernel->boot();

        try {
            $kernel->boot();
            self::fail('A repeated boot must fail.');
        } catch (LifecycleException $exception) {
            self::assertStringContainsString('boot', strtolower($exception->getMessage()));
        }

        self::assertSame(KernelState::Booted, $kernel->state());
        self::assertSame(0, $kernel->activeOperationCount());
    }

    public function testOperationCannotBeginBeforeBoot(): void
    {
        $kernel = new Kernel();

        $this->expectException(LifecycleException::class);
        $this->expectExceptionMessageMatches('/begin an operation/i');

        $kernel->beginOperation();
    }

    public function testOperationCannotBeginAfterShutdown(): void
    {
        $kernel = new Kernel();
        $kernel->boot();
        $kernel->shutdown();

        $this->expectException(LifecycleException::class);
        $this->expectExceptionMessageMatches('/begin an operation/i');

        $kernel->beginOperation();
    }

    public function testOperationsAreDistinctAndMayOverlap(): void
    {
        $kernel = new Kernel();
        $kernel->boot();

        $first = $kernel->beginOperation();
        $second = $kernel->beginOperation();

        self::assertNotSame($first, $second);
        self::assertSame(2, $kernel->activeOperationCount());
    }

    public function testEndingOneOperationLeavesOtherOperationsActive(): void
    {
        $kernel = new Kernel();
        $kernel->boot();
        $first = $kernel->beginOperation();
        $second = $kernel->beginOperation();

        $kernel->endOperation($first);

        self::assertSame(1, $kernel->activeOperationCount());
        $kernel->endOperation($second);
        self::assertSame(0, $kernel->activeOperationCount());
    }

    public function testForeignOperationIsRejectedWithoutMutatingEitherKernel(): void
    {
        $owner = new Kernel();
        $foreign = new Kernel();
        $owner->boot();
        $foreign->boot();
        $operation = $owner->beginOperation();

        try {
            $foreign->endOperation($operation);
            self::fail('A foreign operation must fail.');
        } catch (LifecycleException $exception) {
            self::assertStringContainsString('owned', strtolower($exception->getMessage()));
        }

        self::assertSame(1, $owner->activeOperationCount());
        self::assertSame(0, $foreign->activeOperationCount());
        self::assertSame(KernelState::Booted, $owner->state());
        self::assertSame(KernelState::Booted, $foreign->state());
    }

    public function testOperationCannotEndTwiceWithoutCountCorruption(): void
    {
        $kernel = new Kernel();
        $kernel->boot();
        $operation = $kernel->beginOperation();
        $kernel->endOperation($operation);

        try {
            $kernel->endOperation($operation);
            self::fail('An already-ended operation must fail.');
        } catch (LifecycleException $exception) {
            self::assertStringContainsString('not active', strtolower($exception->getMessage()));
        }

        self::assertSame(0, $kernel->activeOperationCount());
        self::assertSame(KernelState::Booted, $kernel->state());
    }

    public function testShutdownWithActiveOperationsFailsWithoutMutation(): void
    {
        $kernel = new Kernel();
        $kernel->boot();
        $kernel->beginOperation();

        try {
            $kernel->shutdown();
            self::fail('Shutdown with active operations must fail.');
        } catch (LifecycleException $exception) {
            self::assertStringContainsString('remain active', strtolower($exception->getMessage()));
        }

        self::assertSame(KernelState::Booted, $kernel->state());
        self::assertSame(1, $kernel->activeOperationCount());
    }

    public function testKernelShutsDownAfterAllOperationsEnd(): void
    {
        $kernel = new Kernel();
        $kernel->boot();
        $operation = $kernel->beginOperation();
        $kernel->endOperation($operation);

        $kernel->shutdown();

        self::assertSame(KernelState::Shutdown, $kernel->state());
        self::assertSame(0, $kernel->activeOperationCount());
    }

    public function testRepeatedShutdownFailsWithoutMutation(): void
    {
        $kernel = new Kernel();
        $kernel->boot();
        $kernel->shutdown();

        try {
            $kernel->shutdown();
            self::fail('A repeated shutdown must fail.');
        } catch (LifecycleException $exception) {
            self::assertStringContainsString('shut down', strtolower($exception->getMessage()));
        }

        self::assertSame(KernelState::Shutdown, $kernel->state());
        self::assertSame(0, $kernel->activeOperationCount());
    }

    public function testEndedOperationCannotBeEndedAfterShutdown(): void
    {
        $kernel = new Kernel();
        $kernel->boot();
        $operation = $kernel->beginOperation();
        $kernel->endOperation($operation);
        $kernel->shutdown();

        try {
            $kernel->endOperation($operation);
            self::fail('An operation cannot end after Kernel shutdown.');
        } catch (LifecycleException $exception) {
            self::assertStringContainsString('shutdown', strtolower($exception->getMessage()));
        }

        self::assertSame(KernelState::Shutdown, $kernel->state());
        self::assertSame(0, $kernel->activeOperationCount());
    }

    public function testLifecycleFailuresImplementPublicExceptionBoundary(): void
    {
        $kernel = new Kernel();

        try {
            $kernel->shutdown();
            self::fail('Shutdown before boot must fail.');
        } catch (Throwable $exception) {
            self::assertInstanceOf(LifecycleException::class, $exception);
            self::assertInstanceOf(ExceptionInterface::class, $exception);
        }
    }

    public function testShutdownKernelCannotBeBootedAgain(): void
    {
        $kernel = new Kernel();
        $kernel->boot();
        $kernel->shutdown();

        try {
            $kernel->boot();
            self::fail('A shutdown Kernel must not boot again.');
        } catch (LifecycleException $exception) {
            self::assertStringContainsString('boot', strtolower($exception->getMessage()));
        }

        self::assertSame(KernelState::Shutdown, $kernel->state());
    }
}
