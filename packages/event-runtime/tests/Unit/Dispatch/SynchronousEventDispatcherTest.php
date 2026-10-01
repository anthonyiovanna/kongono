<?php

declare(strict_types=1);

namespace Kongono\EventRuntime\Tests\Unit\Dispatch;

use Closure;
use Error;
use Kongono\EventContract\ApplicationEvent;
use Kongono\EventContract\EventHandler;
use Kongono\EventContract\Exception\InvalidEventContractException;
use Kongono\EventContract\Registration\EventRegistration;
use Kongono\EventContract\Registration\EventRegistrationSet;
use Kongono\EventRuntime\Dispatch\SynchronousEventDispatcher;
use Kongono\EventRuntime\Tests\Fixture\DerivedEvent;
use Kongono\EventRuntime\Tests\Fixture\EventEnum;
use Kongono\EventRuntime\Tests\Fixture\FirstEvent;
use Kongono\EventRuntime\Tests\Fixture\RecordingHandler;
use Kongono\EventRuntime\Tests\Fixture\SecondEvent;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Throwable;
use TypeError;

final class SynchronousEventDispatcherTest extends TestCase
{
    public function testEmptyAndUnmatchedTypedEventsAreSuccessfulNoOps(): void
    {
        self::assertTrue(class_exists(FirstEvent::class));
        self::assertTrue(class_exists(SecondEvent::class));
        self::assertTrue(enum_exists(EventEnum::class));
        $empty = new SynchronousEventDispatcher(new EventRegistrationSet([]));
        $empty->dispatch(new FirstEvent());

        $handler = new RecordingHandler();
        $dispatcher = new SynchronousEventDispatcher(new EventRegistrationSet([
            new EventRegistration(FirstEvent::class, $handler),
        ]));
        $dispatcher->dispatch(new SecondEvent());
        $dispatcher->dispatch(new class implements ApplicationEvent {});
        $dispatcher->dispatch(EventEnum::Example);
        $longType = require __DIR__ . '/../../Fixture/LongNameEvent.php';
        self::assertIsString($longType);
        self::assertGreaterThan(255, strlen($longType));
        /** @var ApplicationEvent $longEvent */
        $longEvent = new $longType();
        $dispatcher->dispatch($longEvent);
        self::assertSame([], $handler->events);

        $dispatcher->dispatch(new FirstEvent());
        self::assertCount(1, $handler->events);
    }

    public function testExactClassOrderInterspersedNonmatchesAndSameInstanceDelivery(): void
    {
        self::assertTrue(class_exists(FirstEvent::class));
        self::assertTrue(class_exists(SecondEvent::class));
        self::assertTrue(class_exists(DerivedEvent::class));
        $order = [];
        $received = [];
        $first = self::handler(static function (ApplicationEvent $event) use (&$order, &$received): void {
            $order[] = 'first';
            $received[] = $event;
        });
        $second = self::handler(static function (ApplicationEvent $event) use (&$order, &$received): void {
            $order[] = 'second';
            $received[] = $event;
        });
        $other = new RecordingHandler();
        $derived = new RecordingHandler();
        $dispatcher = new SynchronousEventDispatcher(new EventRegistrationSet([
            new EventRegistration(SecondEvent::class, $other),
            new EventRegistration(FirstEvent::class, $first),
            new EventRegistration(DerivedEvent::class, $derived),
            new EventRegistration(FirstEvent::class, $second),
        ]));

        $event = new FirstEvent('same-object');
        $dispatcher->dispatch($event);
        self::assertSame(['first', 'second'], $order);
        self::assertSame([$event, $event], $received);
        self::assertSame([], $other->events);
        self::assertSame([], $derived->events);

        $child = new DerivedEvent();
        $dispatcher->dispatch($child);
        self::assertSame(['first', 'second'], $order);
        self::assertSame([$child], $derived->events);
        $unrelated = new SecondEvent();
        $dispatcher->dispatch($unrelated);
        self::assertSame([$unrelated], $other->events);
    }

    public function testDistinctHandlersOfOneClassAndCanonicalAliasObjectBothMatch(): void
    {
        self::assertTrue(class_exists(FirstEvent::class));
        $left = new RecordingHandler();
        $right = new RecordingHandler();
        $dispatcher = new SynchronousEventDispatcher(new EventRegistrationSet([
            new EventRegistration(FirstEvent::class, $left),
            new EventRegistration(FirstEvent::class, $right),
        ]));
        $alias = 'Kongono\\EventRuntime\\Tests\\Fixture\\FirstEventAliasPhase22C';
        self::assertTrue(class_alias(FirstEvent::class, $alias, false));
        $event = self::instantiateLoadedAlias($alias);
        self::assertInstanceOf(FirstEvent::class, $event);
        self::assertSame(FirstEvent::class, get_class($event));
        $dispatcher->dispatch($event);
        self::assertSame([$event], $left->events);
        self::assertSame([$event], $right->events);
    }

    public function testSixtyFourValidRegistrationsRunOnceAndSixtyFifthFailsBeforeAnyEffect(): void
    {
        self::assertTrue(class_exists(FirstEvent::class));
        $handlers = [];
        $registrations = [];
        for ($i = 0; $i < 64; ++$i) {
            $handlers[] = new RecordingHandler();
            $registrations[] = new EventRegistration(FirstEvent::class, $handlers[$i]);
        }
        $dispatcher = new SynchronousEventDispatcher(new EventRegistrationSet($registrations));
        $event = new FirstEvent();
        $dispatcher->dispatch($event);
        foreach ($handlers as $handler) {
            self::assertSame([$event], $handler->events);
        }

        $tooMany = $registrations;
        $tooMany[] = $registrations[0];
        try {
            new EventRegistrationSet($tooMany);
            self::fail('Expected the collection bound to reject before construction.');
        } catch (InvalidEventContractException $failure) {
            self::assertSame('KGN-EVENT-CONTRACT-2214', $failure->diagnosticId());
        }
        foreach ($handlers as $handler) {
            self::assertSame([$event], $handler->events);
        }
    }

    public function testNativeInputAndInvalidRegistrationFailBeforeHandlerEffect(): void
    {
        self::assertTrue(class_exists(FirstEvent::class));
        $handler = new RecordingHandler();
        $dispatcher = new SynchronousEventDispatcher(new EventRegistrationSet([
            new EventRegistration(FirstEvent::class, $handler),
        ]));
        try {
            (new \ReflectionMethod($dispatcher, 'dispatch'))->invoke($dispatcher, new \stdClass());
            self::fail('Expected native marker type enforcement.');
        } catch (TypeError) {
            self::assertSame([], $handler->events);
        }
        try {
            new EventRegistration('Unavailable\\NotLoadedPhase22C', $handler);
            self::fail('Expected inert contract validation.');
        } catch (InvalidEventContractException $failure) {
            self::assertSame('KGN-EVENT-CONTRACT-2211', $failure->diagnosticId());
            self::assertSame([], $handler->events);
        }
        $dispatcher->dispatch(new FirstEvent());
        self::assertCount(1, $handler->events);
    }

    public function testFirstExceptionAndErrorKeepObjectIdentityAndEarlierEffects(): void
    {
        self::assertTrue(class_exists(FirstEvent::class));
        foreach ([new RuntimeException('private-handler-exception'), new Error('private-handler-error')] as $original) {
            $effects = [];
            $dispatcher = new SynchronousEventDispatcher(new EventRegistrationSet([
                new EventRegistration(FirstEvent::class, self::handler(static function () use (&$effects): void {
                    $effects[] = 'before';
                })),
                new EventRegistration(FirstEvent::class, self::handler(static function () use ($original): never {
                    throw $original;
                })),
                new EventRegistration(FirstEvent::class, self::handler(static function () use (&$effects): void {
                    $effects[] = 'after';
                })),
            ]));
            try {
                $dispatcher->dispatch(new FirstEvent());
                self::fail('Expected the original handler failure.');
            } catch (Throwable $caught) {
                self::assertSame($original, $caught);
            }
            self::assertSame(['before'], $effects);
            $dispatcher->dispatch(new SecondEvent());
            self::assertSame(['before'], $effects);
        }
    }

    public function testHandlerBusinessNarrowingFailureRemainsOriginal(): void
    {
        self::assertTrue(class_exists(FirstEvent::class));
        $failure = new RuntimeException('application-owned mismatch');
        $handler = self::handler(static function () use ($failure): never {
            throw $failure;
        });
        $dispatcher = new SynchronousEventDispatcher(new EventRegistrationSet([
            new EventRegistration(FirstEvent::class, $handler),
        ]));
        try {
            $dispatcher->dispatch(new FirstEvent());
            self::fail('Expected application failure.');
        } catch (RuntimeException $caught) {
            self::assertSame($failure, $caught);
        }
    }

    public function testPreloadedDispatchDoesNotInvokeAutoloaderOrEventStringifier(): void
    {
        self::assertTrue(class_exists(FirstEvent::class));
        $handler = new RecordingHandler();
        $dispatcher = new SynchronousEventDispatcher(new EventRegistrationSet([
            new EventRegistration(FirstEvent::class, $handler),
        ]));
        $autoloadCalls = 0;
        $loader = static function () use (&$autoloadCalls): void {
            ++$autoloadCalls;
        };
        spl_autoload_register($loader);
        try {
            $event = new FirstEvent('not-for-stringification');
            $dispatcher->dispatch($event);
            self::assertSame([$event], $handler->events);
            self::assertSame(0, $autoloadCalls);
        } finally {
            spl_autoload_unregister($loader);
        }
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

    private static function instantiateLoadedAlias(string $alias): FirstEvent
    {
        if (!class_exists($alias, false)) {
            throw new \LogicException('Test alias was not loaded.');
        }
        $event = new $alias();
        if (!$event instanceof FirstEvent) {
            throw new \LogicException('Test alias did not create the expected event.');
        }

        return $event;
    }
}
