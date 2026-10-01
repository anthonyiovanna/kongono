<?php

declare(strict_types=1);

namespace Kongono\EventContract\Registration;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\EventContract\ApplicationEvent;
use Kongono\EventContract\EventHandler;
use Kongono\EventContract\Exception\InvalidEventContractException;
use ReflectionClass;

/** Binds one exact, already-loaded event class to one caller-owned handler. */
#[Api(ApiStatus::Experimental)]
final readonly class EventRegistration
{
    /** @var class-string<ApplicationEvent> */
    private string $eventType;

    /** The string is validated here; supplying a class string does not load it. */
    public function __construct(string $eventType, private EventHandler $handler)
    {
        $length = strlen($eventType);
        if (0 === $length) {
            throw InvalidEventContractException::invalidEventType('event_type_grammar');
        }
        if (255 < $length) {
            throw InvalidEventContractException::contractBoundExceeded('event_type_bytes');
        }
        if (1 !== preg_match('/\A[A-Za-z_][A-Za-z0-9_]*(?:\\\\[A-Za-z_][A-Za-z0-9_]*)*\z/D', $eventType)) {
            throw InvalidEventContractException::invalidEventType('event_type_grammar');
        }
        if (!class_exists($eventType, false)) {
            throw InvalidEventContractException::invalidEventType('event_type_not_loaded');
        }

        $class = new ReflectionClass($eventType);
        if ($class->isAnonymous() || $class->isAbstract() || $class->isEnum() || $class->isInterface() || $class->isTrait()) {
            throw InvalidEventContractException::invalidEventType('event_type_not_concrete');
        }
        if (!$class->implementsInterface(ApplicationEvent::class) || !is_a($eventType, ApplicationEvent::class, true)) {
            throw InvalidEventContractException::invalidEventType('event_type_not_event');
        }
        if ($class->getName() !== $eventType) {
            throw InvalidEventContractException::invalidEventType('event_type_not_canonical');
        }

        $this->eventType = $eventType;
    }

    /** @return class-string<ApplicationEvent> */
    public function eventType(): string
    {
        return $this->eventType;
    }

    public function handler(): EventHandler
    {
        return $this->handler;
    }
}
