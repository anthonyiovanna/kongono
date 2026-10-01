<?php

declare(strict_types=1);

namespace EventMinimalApp;

use Closure;
use Kongono\EventContract\ApplicationEvent;
use Kongono\EventContract\EventHandler;
use Kongono\EventContract\Registration\EventRegistration;
use Kongono\EventContract\Registration\EventRegistrationSet;
use Kongono\EventRuntime\Dispatch\SynchronousEventDispatcher;
use RuntimeException;

/** Application-owned explicit composition; the supplied closures own their effects. */
final class EventComposition
{
    /**
     * @param Closure(string, InvoicePaid): bool $authorize
     * @param Closure(InvoicePaid): void $recordReceipt
     * @param Closure(InvoicePaid): void $recordLedger
     */
    public static function create(Closure $authorize, Closure $recordReceipt, Closure $recordLedger): SynchronousEventDispatcher
    {
        if (!class_exists(InvoicePaid::class)) {
            throw new RuntimeException('Application event class could not be loaded.');
        }

        $receipt = self::handler('receipt', $authorize, $recordReceipt);
        $ledger = self::handler('ledger', $authorize, $recordLedger);
        $registrations = new EventRegistrationSet([
            new EventRegistration(InvoicePaid::class, $receipt),
            new EventRegistration(InvoicePaid::class, $ledger),
        ]);

        return new SynchronousEventDispatcher($registrations);
    }

    /**
     * @param Closure(string, InvoicePaid): bool $authorize
     * @param Closure(InvoicePaid): void $effect
     */
    private static function handler(string $action, Closure $authorize, Closure $effect): EventHandler
    {
        return new class ($action, $authorize, $effect) implements EventHandler {
            public function __construct(
                private readonly string $action,
                private readonly Closure $authorize,
                private readonly Closure $effect,
            ) {}

            public function handle(ApplicationEvent $event): void
            {
                if (!$event instanceof InvoicePaid) {
                    throw new RuntimeException('Handler received the wrong application event.');
                }
                if (true !== ($this->authorize)($this->action, $event)) {
                    throw new RuntimeException('Application effect was denied.');
                }

                ($this->effect)($event);
            }
        };
    }
}
