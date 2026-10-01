<?php

declare(strict_types=1);

namespace EventMinimalApp\Tests;

use Error;
use EventMinimalApp\EventComposition;
use EventMinimalApp\InvoicePaid;
use InvalidArgumentException;
use Kongono\EventContract\ApplicationEvent;
use Kongono\EventContract\Registration\EventRegistrationSet;
use Kongono\EventRuntime\Dispatch\SynchronousEventDispatcher;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Throwable;

final class EventMinimalApplicationTest extends TestCase
{
    public function testCompositionLoadsEventAndDoesNotAuthorizeOrRunEffects(): void
    {
        $authorizations = [];
        $effects = [];
        $dispatcher = EventComposition::create(
            static function (string $action, InvoicePaid $event) use (&$authorizations): bool {
                $authorizations[] = [$action, $event];

                return true;
            },
            static function (InvoicePaid $event) use (&$effects): void {
                $effects[] = $event;
            },
            static function (InvoicePaid $event) use (&$effects): void {
                $effects[] = $event;
            },
        );

        self::assertTrue(class_exists(InvoicePaid::class, false));
        self::assertInstanceOf(SynchronousEventDispatcher::class, $dispatcher);
        self::assertSame([], $authorizations);
        self::assertSame([], $effects);
    }

    public function testExplicitOrderAndSameEventObject(): void
    {
        $effects = [];
        $authorized = [];
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
        $event = new InvoicePaid('INV-1');
        $dispatcher->dispatch($event);

        self::assertSame([['receipt', $event], ['ledger', $event]], $authorized);
        self::assertSame([['receipt', $event], ['ledger', $event]], $effects);
        self::assertSame($event, $effects[0][1]);
        self::assertSame($event, $effects[1][1]);
    }

    public function testInvalidBusinessInputAndIdleEventsHaveNoEffects(): void
    {
        $effects = [];
        $dispatcher = EventComposition::create(
            static fn(string $action, InvoicePaid $event): bool => true,
            static function (InvoicePaid $event) use (&$effects): void {
                $effects[] = $event;
            },
            static function (InvoicePaid $event) use (&$effects): void {
                $effects[] = $event;
            },
        );

        try {
            new InvoicePaid('bad private value');
            self::fail('Expected business-input validation.');
        } catch (InvalidArgumentException $failure) {
            self::assertSame('Invoice identifier is invalid.', $failure->getMessage());
        }
        $dispatcher->dispatch(new UnrelatedEvent());
        (new SynchronousEventDispatcher(new EventRegistrationSet([])))->dispatch(new InvoicePaid('INV-2'));
        self::assertSame([], $effects);
    }

    public function testEachDeniedEffectIsSkippedAndEarlierEffectsRemainTruthful(): void
    {
        foreach (['receipt', 'ledger'] as $denied) {
            $effects = [];
            $authorizations = [];
            $dispatcher = EventComposition::create(
                static function (string $action, InvoicePaid $event) use ($denied, &$authorizations): bool {
                    $authorizations[] = $action;

                    return $action !== $denied;
                },
                static function (InvoicePaid $event) use (&$effects): void {
                    $effects[] = 'receipt';
                },
                static function (InvoicePaid $event) use (&$effects): void {
                    $effects[] = 'ledger';
                },
            );
            try {
                $dispatcher->dispatch(new InvoicePaid('INV-3'));
                self::fail('Expected application authorization denial.');
            } catch (RuntimeException $failure) {
                self::assertSame('Application effect was denied.', $failure->getMessage());
            }
            self::assertSame('receipt' === $denied ? [] : ['receipt'], $effects);
            self::assertSame('receipt' === $denied ? ['receipt'] : ['receipt', 'ledger'], $authorizations);
        }
    }

    public function testOriginalExceptionAndErrorEscapeAfterEarlierEffectWithoutRetry(): void
    {
        foreach ([new RuntimeException('private failure'), new Error('private error')] as $original) {
            $effects = [];
            $dispatcher = EventComposition::create(
                static fn(string $action, InvoicePaid $event): bool => true,
                static function (InvoicePaid $event) use (&$effects): void {
                    $effects[] = 'receipt';
                },
                static function (InvoicePaid $event) use ($original, &$effects): never {
                    $effects[] = 'ledger-attempt';
                    throw $original;
                },
            );
            try {
                $dispatcher->dispatch(new InvoicePaid('INV-4'));
                self::fail('Expected the original handler throwable.');
            } catch (Throwable $caught) {
                self::assertSame($original, $caught);
            }
            self::assertSame(['receipt', 'ledger-attempt'], $effects);
        }
    }

    public function testLifetimeSafeReuseAfterSuccessDenialAndFailureChecksPolicyAgain(): void
    {
        $state = new class {
            public string $mode = 'allow';
        };
        $policyCalls = [];
        $effects = [];
        $original = new RuntimeException('private ledger failure');
        $dispatcher = EventComposition::create(
            static function (string $action, InvoicePaid $event) use ($state, &$policyCalls): bool {
                $policyCalls[] = [$action, $event->invoiceId];

                return 'deny-ledger' !== $state->mode || 'ledger' !== $action;
            },
            static function (InvoicePaid $event) use (&$effects): void {
                $effects[] = ['receipt', $event->invoiceId];
            },
            static function (InvoicePaid $event) use (&$effects, $state, $original): void {
                if ('fail-ledger' === $state->mode) {
                    throw $original;
                }
                $effects[] = ['ledger', $event->invoiceId];
            },
        );

        $dispatcher->dispatch(new InvoicePaid('INV-5'));
        $state->mode = 'deny-ledger';
        try {
            $dispatcher->dispatch(new InvoicePaid('INV-6'));
            self::fail('Expected denial.');
        } catch (RuntimeException $failure) {
            self::assertSame('Application effect was denied.', $failure->getMessage());
        }
        $state->mode = 'fail-ledger';
        try {
            $dispatcher->dispatch(new InvoicePaid('INV-7'));
            self::fail('Expected handler failure.');
        } catch (RuntimeException $failure) {
            self::assertSame($original, $failure);
        }
        $state->mode = 'allow';
        $dispatcher->dispatch(new InvoicePaid('INV-8'));

        self::assertSame([
            ['receipt', 'INV-5'], ['ledger', 'INV-5'],
            ['receipt', 'INV-6'],
            ['receipt', 'INV-7'],
            ['receipt', 'INV-8'], ['ledger', 'INV-8'],
        ], $effects);
        self::assertSame([
            ['receipt', 'INV-5'], ['ledger', 'INV-5'],
            ['receipt', 'INV-6'], ['ledger', 'INV-6'],
            ['receipt', 'INV-7'], ['ledger', 'INV-7'],
            ['receipt', 'INV-8'], ['ledger', 'INV-8'],
        ], $policyCalls);
    }

    public function testSeparateCompositionsKeepApplicationEvidenceSeparate(): void
    {
        $first = [];
        $second = [];
        $allow = static fn(string $action, InvoicePaid $event): bool => true;
        $firstDispatcher = EventComposition::create(
            $allow,
            static function (InvoicePaid $event) use (&$first): void {
                $first[] = $event->invoiceId;
            },
            static function (InvoicePaid $event) use (&$first): void {
                $first[] = $event->invoiceId;
            },
        );
        $secondDispatcher = EventComposition::create(
            $allow,
            static function (InvoicePaid $event) use (&$second): void {
                $second[] = $event->invoiceId;
            },
            static function (InvoicePaid $event) use (&$second): void {
                $second[] = $event->invoiceId;
            },
        );

        $firstDispatcher->dispatch(new InvoicePaid('A'));
        $secondDispatcher->dispatch(new InvoicePaid('B'));
        self::assertSame(['A', 'A'], $first);
        self::assertSame(['B', 'B'], $second);
    }
}

final readonly class UnrelatedEvent implements ApplicationEvent {}
