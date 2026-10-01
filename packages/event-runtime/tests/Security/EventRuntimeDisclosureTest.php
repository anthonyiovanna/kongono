<?php

declare(strict_types=1);

namespace Kongono\EventRuntime\Tests\Security;

use Kongono\EventContract\ApplicationEvent;
use Kongono\EventContract\EventHandler;
use Kongono\EventContract\Registration\EventRegistration;
use Kongono\EventContract\Registration\EventRegistrationSet;
use Kongono\EventRuntime\Dispatch\SynchronousEventDispatcher;
use Kongono\EventRuntime\Exception\ReentrantDispatchException;
use Kongono\EventRuntime\Tests\Fixture\FirstEvent;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class EventRuntimeDisclosureTest extends TestCase
{
    public function testDeliberateFailureHasFixedSafePresentationWithTraceArgumentsEnabled(): void
    {
        self::assertTrue(class_exists(FirstEvent::class));
        $original = ini_get('zend.exception_ignore_args');
        self::assertIsString($original);
        try {
            self::assertIsString(ini_set('zend.exception_ignore_args', '0'));
            self::assertSame('0', ini_get('zend.exception_ignore_args'));
            $secret = 'PRIVATE-PAYLOAD-HANDLER-TOKEN-22C';
            $event = new FirstEvent($secret);
            $handler = new class ($secret) implements EventHandler {
                public ?ReentrantDispatchException $caught = null;
                public int $captures = 0;
                private ?SynchronousEventDispatcher $dispatcher = null;

                public function __construct(private readonly string $secret) {}

                public function bind(SynchronousEventDispatcher $dispatcher): void
                {
                    $this->dispatcher = $dispatcher;
                }

                public function handle(ApplicationEvent $event): void
                {
                    $dispatcher = $this->dispatcher;
                    if (null === $dispatcher) {
                        throw new \LogicException('Test dispatcher was not bound.');
                    }
                    try {
                        $dispatcher->dispatch($event);
                    } catch (ReentrantDispatchException $failure) {
                        $this->caught = $failure;
                        ++$this->captures;
                    }
                }

                public function __toString(): string
                {
                    throw new RuntimeException($this->secret);
                }
            };
            $dispatcher = new SynchronousEventDispatcher(new EventRegistrationSet([new EventRegistration(FirstEvent::class, $handler)]));
            $handler->bind($dispatcher);
            $dispatcher->dispatch($event);
            self::assertSame(1, $handler->captures);
            $caught = $handler->caught;
            if (!$caught instanceof ReentrantDispatchException) {
                self::fail('Expected a captured nested dispatch rejection.');
            }
            self::assertSame('Event dispatch is already active.', $caught->getMessage());
            self::assertSame('KGN-EVENT-RUNTIME-2221', $caught->diagnosticId());
            self::assertSame('reentrant_dispatch', $caught->diagnosticContext()->fields()[0]->value());
            foreach ([$caught->getMessage(), (string) $caught, $caught->diagnosticTitle(), $caught->diagnosticRemediation()] as $presentation) {
                self::assertStringNotContainsString($secret, $presentation);
            }
            self::assertSame(null, $caught->getPrevious());
        } finally {
            self::assertIsString(ini_set('zend.exception_ignore_args', $original));
            self::assertSame($original, ini_get('zend.exception_ignore_args'));
        }
    }

    public function testApplicationThrowableIsPreservedWithoutPresentationClaim(): void
    {
        self::assertTrue(class_exists(FirstEvent::class));
        $failure = new RuntimeException('application-private-error');
        $handler = new class ($failure) implements EventHandler {
            public function __construct(private readonly RuntimeException $failure) {}

            public function handle(ApplicationEvent $event): void
            {
                throw $this->failure;
            }
        };
        $dispatcher = new SynchronousEventDispatcher(new EventRegistrationSet([new EventRegistration(FirstEvent::class, $handler)]));
        try {
            $dispatcher->dispatch(new FirstEvent());
            self::fail('Expected the application throwable.');
        } catch (RuntimeException $caught) {
            self::assertSame($failure, $caught);
        }
    }
}
