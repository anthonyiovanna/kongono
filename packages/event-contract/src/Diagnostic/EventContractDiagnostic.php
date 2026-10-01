<?php

declare(strict_types=1);

namespace Kongono\EventContract\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

/** Closed Safe diagnostic vocabulary for inert event contracts. */
#[Api(ApiStatus::Internal)]
enum EventContractDiagnostic: string
{
    case InvalidEventType = 'KGN-EVENT-CONTRACT-2211';
    case InvalidRegistration = 'KGN-EVENT-CONTRACT-2212';
    case DuplicateBinding = 'KGN-EVENT-CONTRACT-2213';
    case ContractBoundExceeded = 'KGN-EVENT-CONTRACT-2214';

    public function contextSchema(): DiagnosticContextSchema
    {
        return new DiagnosticContextSchema($this->operation(), $this->invariant(), [
            new DiagnosticFieldSchema('violation', 'Violation', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
        ]);
    }

    public function context(string $violation): DiagnosticContext
    {
        $allowed = match ($this) {
            self::InvalidEventType => ['event_type_grammar', 'event_type_not_loaded', 'event_type_not_concrete', 'event_type_not_event', 'event_type_not_canonical'],
            self::InvalidRegistration => ['registrations_not_list', 'invalid_registration_entry'],
            self::DuplicateBinding => ['duplicate_binding'],
            self::ContractBoundExceeded => ['event_type_bytes', 'registration_count'],
        };

        return new DiagnosticContext($this->contextSchema(), [
            'violation' => in_array($violation, $allowed, true) ? $violation : 'unsupported_violation',
        ]);
    }

    public function title(): string
    {
        return match ($this) {
            self::InvalidEventType => 'Event type is invalid',
            self::InvalidRegistration => 'Event registration list is invalid',
            self::DuplicateBinding => 'Event binding is duplicated',
            self::ContractBoundExceeded => 'Event contract bound is exceeded',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::InvalidEventType => 'Supply one already-loaded canonical concrete application-event class',
            self::InvalidRegistration => 'Supply a list of validated event registrations',
            self::DuplicateBinding => 'Supply each exact event-type and handler-object pair once',
            self::ContractBoundExceeded => 'Reduce the event type or registration list to the documented limit',
        };
    }

    private function operation(): string
    {
        return match ($this) {
            self::InvalidEventType => 'Create event registration',
            self::InvalidRegistration, self::DuplicateBinding => 'Create event registration set',
            self::ContractBoundExceeded => 'Validate event contract bounds',
        };
    }

    private function invariant(): string
    {
        return match ($this) {
            self::InvalidEventType => 'An exact event type is a bounded canonical already-loaded concrete marker class',
            self::InvalidRegistration => 'Registrations form a finite ordered list of validated pairs',
            self::DuplicateBinding => 'One handler object may bind to each exact event class only once',
            self::ContractBoundExceeded => 'Event type and collection bounds are finite before effects',
        };
    }
}
