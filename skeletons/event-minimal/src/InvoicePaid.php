<?php

declare(strict_types=1);

namespace EventMinimalApp;

use InvalidArgumentException;
use Kongono\EventContract\ApplicationEvent;

/** An application-owned occurrence with a small validated scalar payload. */
final readonly class InvoicePaid implements ApplicationEvent
{
    public function __construct(public string $invoiceId)
    {
        if (1 !== preg_match('/\A[A-Za-z0-9][A-Za-z0-9_-]{0,63}\z/D', $invoiceId)) {
            throw new InvalidArgumentException('Invoice identifier is invalid.');
        }
    }
}
