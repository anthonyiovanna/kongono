<?php

declare(strict_types=1);

namespace Kongono\ExtensionHost\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

/** Owns the fixed bounded diagnostics for trusted extension loading and lifecycle accounting. */
#[Api(ApiStatus::Internal)]
enum ExtensionHostDiagnostic: string
{
    case InvalidGeneratedHost = 'KGN-EXTENSION-HOST-1201';
    case HostUnavailable = 'KGN-EXTENSION-HOST-1202';
    case LifecycleFailuresCombined = 'KGN-EXTENSION-HOST-1203';

    public function contextSchema(): DiagnosticContextSchema
    {
        $fields = match ($this) {
            self::InvalidGeneratedHost => [
                new DiagnosticFieldSchema('violation', 'Violation', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
            ],
            self::HostUnavailable => [
                new DiagnosticFieldSchema('violation', 'Violation', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
                new DiagnosticFieldSchema('state', 'Lifecycle state', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
            ],
            self::LifecycleFailuresCombined => [
                new DiagnosticFieldSchema('primary_present', 'Primary present', DiagnosticValueKind::Boolean, DiagnosticDisclosure::Safe),
                new DiagnosticFieldSchema('cleanup_count', 'Cleanup failure count', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe),
            ],
        };

        return new DiagnosticContextSchema($this->operation(), $this->invariant(), $fields);
    }

    /** @param array<string, string|int|bool> $values */
    public function context(array $values): DiagnosticContext
    {
        return new DiagnosticContext($this->contextSchema(), $values);
    }

    public function title(): string
    {
        return match ($this) {
            self::InvalidGeneratedHost => 'Generated extension host profile is invalid',
            self::HostUnavailable => 'Trusted extension host lifecycle is unavailable',
            self::LifecycleFailuresCombined => 'Extension lifecycle primary and cleanup failures were combined',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::InvalidGeneratedHost => 'Supply one exact current content-addressed generated wiring file and matching grant plan',
            self::HostUnavailable => 'Close outstanding operations and use only an active non-retired host lifecycle',
            self::LifecycleFailuresCombined => 'Retire uncertain work and inspect the preserved primary and ordered cleanup failures',
        };
    }

    private function operation(): string
    {
        return match ($this) {
            self::InvalidGeneratedHost => 'Load exact generated extension wiring',
            self::HostUnavailable => 'Use trusted extension host lifecycle',
            self::LifecycleFailuresCombined => 'Compose extension lifecycle failures',
        };
    }

    private function invariant(): string
    {
        return match ($this) {
            self::InvalidGeneratedHost => 'Artifact, plan, file, classes, accessors, and lifecycle shape must match exactly',
            self::HostUnavailable => 'Only an active host may begin work and shutdown requires zero operation leases',
            self::LifecycleFailuresCombined => 'The exact primary and every ordered cleanup failure must remain observable',
        };
    }
}
