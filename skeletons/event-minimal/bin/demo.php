<?php

declare(strict_types=1);

use EventMinimalApp\EventComposition;
use EventMinimalApp\InvoicePaid;

try {
    set_error_handler(static function (): never {
        throw new RuntimeException('Application bootstrap failed.');
    }, E_WARNING);
    try {
        require dirname(__DIR__) . '/vendor/autoload.php';
    } finally {
        restore_error_handler();
    }

    $effects = [];
    $authorize = static fn(string $action, InvoicePaid $event): bool => 'INV-1' === $event->invoiceId
        && in_array($action, ['receipt', 'ledger'], true);
    $receipt = static function (InvoicePaid $event) use (&$effects): void {
        $effects[] = 'receipt:' . $event->invoiceId;
    };
    $ledger = static function (InvoicePaid $event) use (&$effects): void {
        $effects[] = 'ledger:' . $event->invoiceId;
    };

    $dispatcher = EventComposition::create($authorize, $receipt, $ledger);
    $dispatcher->dispatch(new InvoicePaid('INV-1'));
    if (['receipt:INV-1', 'ledger:INV-1'] !== $effects) {
        throw new RuntimeException('Example effects did not complete in order.');
    }

    fwrite(STDOUT, "event_demo_ok\n");
} catch (Throwable) {
    fwrite(STDERR, "event_demo_failed\n");
    exit(1);
}
