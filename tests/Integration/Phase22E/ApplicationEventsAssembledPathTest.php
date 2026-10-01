<?php

declare(strict_types=1);

namespace Kongono\Tests\Integration\Phase22E;

use Closure;
use Error;
use EventMinimalApp\EventComposition;
use EventMinimalApp\InvoicePaid;
use Kongono\EventContract\ApplicationEvent;
use Kongono\EventContract\EventHandler;
use Kongono\EventContract\Exception\InvalidEventContractException;
use Kongono\EventContract\Registration\EventRegistration;
use Kongono\EventContract\Registration\EventRegistrationSet;
use Kongono\EventRuntime\Dispatch\SynchronousEventDispatcher;
use Kongono\EventRuntime\Exception\ReentrantDispatchException;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use RuntimeException;
use Throwable;
use TypeError;

final class ApplicationEventsAssembledPathTest extends TestCase
{
    public function testDirectCompositionIsInertUntilExactOrderedDelivery(): void
    {
        $authorized = [];
        $effects = [];
        $dispatcher = EventComposition::create(
            static function (string $action, InvoicePaid $event) use (&$authorized): bool {
                $authorized[] = [$action, $event];

                return true;
            },
            static function (InvoicePaid $event) use (&$effects): void {
                $effects[] = ['receipt', $event];
            },
            static function (InvoicePaid $event) use (&$effects): void {
                $effects[] = ['ledger', $event];
            },
        );

        self::assertTrue(class_exists(InvoicePaid::class, false));
        self::assertSame([], $authorized);
        self::assertSame([], $effects);
        $dispatcher->dispatch(new class implements ApplicationEvent {});
        self::assertSame([], $authorized);

        $event = new InvoicePaid('PHASE22E-1');
        $dispatcher->dispatch($event);
        self::assertSame([['receipt', $event], ['ledger', $event]], $authorized);
        self::assertSame([['receipt', $event], ['ledger', $event]], $effects);
    }

    public function testRejectedRegistrationsStayInertWhileEmptyAndUnmatchedSetsSucceed(): void
    {
        self::assertTrue(class_exists(InvoicePaid::class));
        $calls = 0;
        $handler = self::handler(static function (ApplicationEvent $event) use (&$calls): void {
            ++$calls;
        });
        $registration = new EventRegistration(InvoicePaid::class, $handler);
        try {
            new EventRegistrationSet([$registration, $registration]);
            self::fail('Expected exact duplicate rejection.');
        } catch (InvalidEventContractException $failure) {
            self::assertSame('KGN-EVENT-CONTRACT-2213', $failure->diagnosticId());
            self::assertSame('duplicate_binding', $failure->diagnosticContext()->fields()[0]->value());
        }

        $secret = 'PRIVATE-PHASE22E-REGISTRATION/NAME';
        try {
            new EventRegistration($secret, $handler);
            self::fail('Expected malformed registration rejection.');
        } catch (InvalidEventContractException $failure) {
            self::assertSame('KGN-EVENT-CONTRACT-2211', $failure->diagnosticId());
            self::assertStringNotContainsString($secret, $failure->getMessage());
            self::assertStringNotContainsString($secret, (string) $failure);
            self::assertSame('event_type_grammar', $failure->diagnosticContext()->fields()[0]->value());
        }

        (new SynchronousEventDispatcher(new EventRegistrationSet([])))->dispatch(new InvoicePaid('PHASE22E-2'));
        (new SynchronousEventDispatcher(new EventRegistrationSet([$registration])))->dispatch(new class implements ApplicationEvent {});
        self::assertSame(0, $calls);
    }

    public function testCurrentAuthorizationPrecedesEachEffectAndDenialPreservesProgress(): void
    {
        foreach (['receipt', 'ledger'] as $denied) {
            $authorizations = [];
            $effects = [];
            $dispatcher = EventComposition::create(
                static function (string $action, InvoicePaid $event) use ($denied, &$authorizations): bool {
                    $authorizations[] = [$action, $event];

                    return $action !== $denied;
                },
                static function (InvoicePaid $event) use (&$effects): void {
                    $effects[] = ['receipt', $event];
                },
                static function (InvoicePaid $event) use (&$effects): void {
                    $effects[] = ['ledger', $event];
                },
            );
            $event = new InvoicePaid('PHASE22E-3');
            try {
                $dispatcher->dispatch($event);
                self::fail('Expected application authorization denial.');
            } catch (RuntimeException $failure) {
                self::assertSame('Application effect was denied.', $failure->getMessage());
            }
            self::assertSame(
                'receipt' === $denied ? [['receipt', $event]] : [['receipt', $event], ['ledger', $event]],
                $authorizations,
            );
            self::assertSame('receipt' === $denied ? [] : [['receipt', $event]], $effects);
        }
    }

    public function testOriginalExceptionAndErrorKeepPartialEffectsAndSafeOuterSummary(): void
    {
        foreach ([new RuntimeException('PRIVATE-PHASE22E-EXCEPTION'), new Error('PRIVATE-PHASE22E-ERROR')] as $original) {
            $effects = [];
            $dispatcher = EventComposition::create(
                static fn(string $action, InvoicePaid $event): bool => true,
                static function (InvoicePaid $event) use (&$effects): void {
                    $effects[] = 'receipt';
                },
                static function (InvoicePaid $event) use ($original): never {
                    throw $original;
                },
            );

            $publicSummary = '';
            try {
                $dispatcher->dispatch(new InvoicePaid('PHASE22E-4'));
                self::fail('Expected original handler failure.');
            } catch (Throwable $caught) {
                self::assertSame($original, $caught);
                $publicSummary = 'event_dispatch_failed';
            }
            self::assertSame(['receipt'], $effects);
            self::assertSame('event_dispatch_failed', $publicSummary);
            self::assertStringNotContainsString($original->getMessage(), $publicSummary);
        }
    }

    public function testNestedRejectionAndSeparateCompositionsLeaveLaterDispatchHealthy(): void
    {
        $firstEffects = [];
        $secondEffects = [];
        $state = new class {
            public bool $nested = true;
            public ?SynchronousEventDispatcher $dispatcher = null;
        };
        $first = EventComposition::create(
            static fn(string $action, InvoicePaid $event): bool => true,
            static function (InvoicePaid $event) use ($state, &$firstEffects): void {
                $firstEffects[] = ['receipt', $event->invoiceId];
                if ($state->nested) {
                    $dispatcher = $state->dispatcher;
                    if (null === $dispatcher) {
                        throw new RuntimeException('Test dispatcher is missing.');
                    }
                    $dispatcher->dispatch($event);
                }
            },
            static function (InvoicePaid $event) use (&$firstEffects): void {
                $firstEffects[] = ['ledger', $event->invoiceId];
            },
        );
        $state->dispatcher = $first;
        $second = EventComposition::create(
            static fn(string $action, InvoicePaid $event): bool => true,
            static function (InvoicePaid $event) use (&$secondEffects): void {
                $secondEffects[] = ['receipt', $event->invoiceId];
            },
            static function (InvoicePaid $event) use (&$secondEffects): void {
                $secondEffects[] = ['ledger', $event->invoiceId];
            },
        );

        try {
            $first->dispatch(new InvoicePaid('PHASE22E-5'));
            self::fail('Expected same-instance nested rejection.');
        } catch (ReentrantDispatchException $failure) {
            self::assertSame('KGN-EVENT-RUNTIME-2221', $failure->diagnosticId());
            self::assertSame('reentrant_dispatch', $failure->diagnosticContext()->fields()[0]->value());
            self::assertStringNotContainsString('PHASE22E-5', (string) $failure);
        }
        $state->nested = false;
        $second->dispatch(new InvoicePaid('PHASE22E-6'));
        $first->dispatch(new InvoicePaid('PHASE22E-7'));
        self::assertSame([
            ['receipt', 'PHASE22E-5'],
            ['receipt', 'PHASE22E-7'], ['ledger', 'PHASE22E-7'],
        ], $firstEffects);
        self::assertSame([['receipt', 'PHASE22E-6'], ['ledger', 'PHASE22E-6']], $secondEffects);
    }

    public function testNativeMisuseFailsBeforeEffectsAndExplicitRepeatIsNotDeduplicated(): void
    {
        $authorizations = 0;
        $effects = [];
        $dispatcher = EventComposition::create(
            static function (string $action, InvoicePaid $event) use (&$authorizations): bool {
                ++$authorizations;

                return true;
            },
            static function (InvoicePaid $event) use (&$effects): void {
                $effects[] = 'receipt';
            },
            static function (InvoicePaid $event) use (&$effects): void {
                $effects[] = 'ledger';
            },
        );

        try {
            (new ReflectionMethod($dispatcher, 'dispatch'))->invoke($dispatcher, new \stdClass());
            self::fail('Expected native marker type enforcement.');
        } catch (TypeError) {
            self::assertSame(0, $authorizations);
            self::assertSame([], $effects);
        }
        $event = new InvoicePaid('PHASE22E-8');
        $dispatcher->dispatch($event);
        $dispatcher->dispatch($event);
        self::assertSame(4, $authorizations);
        self::assertSame(['receipt', 'ledger', 'receipt', 'ledger'], $effects);
    }

    /** @param Closure(ApplicationEvent): void $onHandle */
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
