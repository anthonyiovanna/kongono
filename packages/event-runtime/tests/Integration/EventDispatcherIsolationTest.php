<?php

declare(strict_types=1);

namespace Kongono\EventRuntime\Tests\Integration;

use Closure;
use Fiber;
use Kongono\EventContract\ApplicationEvent;
use Kongono\EventContract\EventHandler;
use Kongono\EventContract\Registration\EventRegistration;
use Kongono\EventContract\Registration\EventRegistrationSet;
use Kongono\EventRuntime\Dispatch\SynchronousEventDispatcher;
use Kongono\EventRuntime\Exception\ReentrantDispatchException;
use Kongono\EventRuntime\Tests\Fixture\FirstEvent;
use Kongono\EventRuntime\Tests\Fixture\SecondEvent;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use WeakReference;

final class EventDispatcherIsolationTest extends TestCase
{
    public function testCaughtRepeatedNestedFailuresLeaveOuterAttemptGuarded(): void
    {
        self::assertTrue(class_exists(FirstEvent::class));
        $failures = new \ArrayObject();
        $calls = 0;
        $dispatcher = new SynchronousEventDispatcher(new EventRegistrationSet([]));
        $handler = self::handler(static function () use (&$dispatcher, &$failures, &$calls): void {
            ++$calls;
            foreach ([new SecondEvent(), new FirstEvent()] as $nested) {
                try {
                    $dispatcher->dispatch($nested);
                } catch (ReentrantDispatchException $failure) {
                    $failures->append($failure);
                }
            }
        });
        $dispatcher = new SynchronousEventDispatcher(new EventRegistrationSet([new EventRegistration(FirstEvent::class, $handler)]));
        $dispatcher->dispatch(new FirstEvent());
        self::assertSame(1, $calls);
        self::assertCount(2, $failures);
        self::assertNotSame($failures->offsetGet(0), $failures->offsetGet(1));
        $dispatcher->dispatch(new SecondEvent());
        self::assertSame(1, $calls);
    }

    public function testEscapedNestedFailureReleasesOuterGuardAndOtherInstanceIsIndependent(): void
    {
        self::assertTrue(class_exists(FirstEvent::class));
        $otherCalls = 0;
        $other = new SynchronousEventDispatcher(new EventRegistrationSet([
            new EventRegistration(FirstEvent::class, self::handler(static function () use (&$otherCalls): void {
                ++$otherCalls;
            })),
        ]));
        $calls = 0;
        $dispatcher = new SynchronousEventDispatcher(new EventRegistrationSet([]));
        $handler = self::handler(static function () use (&$dispatcher, $other, &$calls): void {
            ++$calls;
            $other->dispatch(new FirstEvent());
            if (1 === $calls) {
                $dispatcher->dispatch(new SecondEvent());
            }
        });
        $dispatcher = new SynchronousEventDispatcher(new EventRegistrationSet([new EventRegistration(FirstEvent::class, $handler)]));
        try {
            $dispatcher->dispatch(new FirstEvent());
            self::fail('Nested rejection should escape.');
        } catch (ReentrantDispatchException $failure) {
            self::assertSame('KGN-EVENT-RUNTIME-2221', $failure->diagnosticId());
        }
        self::assertSame(1, $otherCalls);
        $dispatcher->dispatch(new FirstEvent());
        self::assertSame(2, $calls);
        self::assertSame(2, $otherCalls);
    }

    public function testSuspendedFiberKeepsOnlyItsDispatcherBusyUntilUnwind(): void
    {
        self::assertTrue(class_exists(FirstEvent::class));
        $calls = 0;
        $handler = self::handler(static function () use (&$calls): void {
            ++$calls;
            if (1 === $calls) {
                Fiber::suspend('paused');
            }
        });
        $dispatcher = new SynchronousEventDispatcher(new EventRegistrationSet([new EventRegistration(FirstEvent::class, $handler)]));
        $fiber = new Fiber(static function () use ($dispatcher): void {
            $dispatcher->dispatch(new FirstEvent());
        });
        try {
            self::assertSame('paused', $fiber->start());
            try {
                $dispatcher->dispatch(new FirstEvent());
                self::fail('Overlapping dispatch must reject.');
            } catch (ReentrantDispatchException) {
                self::assertSame(1, $calls);
            }
        } finally {
            if ($fiber->isSuspended()) {
                $fiber->resume();
            }
        }
        self::assertTrue($fiber->isTerminated());
        $dispatcher->dispatch(new FirstEvent());
        self::assertSame(2, $calls);
    }

    public function testDispatcherRetainsNoCompletedEventOrFailure(): void
    {
        self::assertTrue(class_exists(FirstEvent::class));
        $dispatcher = new SynchronousEventDispatcher(new EventRegistrationSet([
            new EventRegistration(FirstEvent::class, self::handler(static function (): void {})),
        ]));
        $event = new FirstEvent('private-event');
        $reference = WeakReference::create($event);
        $dispatcher->dispatch($event);
        unset($event);
        self::assertNull($reference->get());

        $handler = self::handler(static function (): void {
            throw new RuntimeException('private-failure');
        });
        $failingDispatcher = new SynchronousEventDispatcher(new EventRegistrationSet([new EventRegistration(FirstEvent::class, $handler)]));
        $failureReference = null;
        try {
            $failingDispatcher->dispatch(new FirstEvent());
        } catch (RuntimeException $caught) {
            $failureReference = WeakReference::create($caught);
        }
        unset($caught);
        self::assertInstanceOf(WeakReference::class, $failureReference);
        self::assertNull($failureReference->get());
    }

    private static function handler(Closure $onHandle): EventHandler
    {
        return new class ($onHandle) implements EventHandler {
            public function __construct(private readonly Closure $onHandle) {}

            public function handle(ApplicationEvent $event): void
            {
                ($this->onHandle)($event);
            }
        };
    }
}
