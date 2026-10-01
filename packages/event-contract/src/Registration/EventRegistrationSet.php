<?php

declare(strict_types=1);

namespace Kongono\EventContract\Registration;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\EventContract\Exception\InvalidEventContractException;

/** Retains a bounded validated list in caller-supplied order without invoking it. */
#[Api(ApiStatus::Experimental)]
final readonly class EventRegistrationSet
{
    private const int MAX_REGISTRATIONS = 64;

    /** @var list<EventRegistration> */
    private array $registrations;

    /** @param array<array-key, mixed> $registrations */
    public function __construct(array $registrations)
    {
        if (count($registrations) > self::MAX_REGISTRATIONS) {
            throw InvalidEventContractException::contractBoundExceeded('registration_count');
        }
        if (!array_is_list($registrations)) {
            throw InvalidEventContractException::invalidRegistration('registrations_not_list');
        }

        $validated = [];
        foreach ($registrations as $registration) {
            if (!$registration instanceof EventRegistration) {
                throw InvalidEventContractException::invalidRegistration('invalid_registration_entry');
            }
            foreach ($validated as $previous) {
                if ($previous->eventType() === $registration->eventType() && $previous->handler() === $registration->handler()) {
                    throw InvalidEventContractException::duplicateBinding();
                }
            }
            $validated[] = $registration;
        }
        $this->registrations = $validated;
    }

    /** @return list<EventRegistration> */
    public function registrations(): array
    {
        return $this->registrations;
    }

    public function count(): int
    {
        return count($this->registrations);
    }
}
