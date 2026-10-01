<?php

declare(strict_types=1);

namespace Kongono\EventRuntime\Dispatch;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\EventContract\ApplicationEvent;
use Kongono\EventContract\Registration\EventRegistrationSet;
use Kongono\EventRuntime\Exception\ReentrantDispatchException;

/** Invokes explicitly registered handlers for one exact application-event class. */
#[Api(ApiStatus::Experimental)]
final class SynchronousEventDispatcher
{
    private bool $busy = false;

    public function __construct(private readonly EventRegistrationSet $registrations) {}

    public function dispatch(ApplicationEvent $event): void
    {
        if ($this->busy) {
            throw ReentrantDispatchException::active();
        }
        $this->busy = true;

        try {
            $eventType = get_class($event);
            foreach ($this->registrations->registrations() as $registration) {
                if ($registration->eventType() === $eventType) {
                    $registration->handler()->handle($event);
                }
            }
        } finally {
            $this->busy = false;
        }
    }
}
