<?php

declare(strict_types=1);

namespace Kongono\Web\Runtime\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

/** Owns fixed package-safe metadata for Web Runtime retirement and cleanup failures. */
#[Api(ApiStatus::Internal)]
enum WebRuntimeDiagnostic: string
{
    case RuntimeRetired = 'KGN-WEB-RUNTIME-1001';
    case DeliveryAndCleanupFailed = 'KGN-WEB-RUNTIME-1002';
    case InvalidGeneratedIntegration = 'KGN-WEB-RUNTIME-1003';

    public function context(?string $violation = null): DiagnosticContext
    {
        $values = self::InvalidGeneratedIntegration === $this
            ? ['violation' => $violation ?? 'invalid_generated_web_runtime']
            : [];

        return new DiagnosticContext(new DiagnosticContextSchema(
            $this->contextOperation(),
            $this->contextViolatedInvariant(),
            $this->contextFields(),
        ), $values);
    }

    /** @return list<DiagnosticFieldSchema> */
    public function contextFields(): array
    {
        return self::InvalidGeneratedIntegration === $this
            ? [new DiagnosticFieldSchema('violation', 'Violation', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe)]
            : [];
    }

    public function contextOperation(): string
    {
        return match ($this) {
            self::RuntimeRetired => 'Use web runtime adapter',
            self::DeliveryAndCleanupFailed => 'Complete web runtime delivery',
            self::InvalidGeneratedIntegration => 'Start generated web runtime integration',
        };
    }

    public function contextViolatedInvariant(): string
    {
        return match ($this) {
            self::RuntimeRetired => 'A retired runtime adapter must not accept another delivery',
            self::DeliveryAndCleanupFailed => 'Delivery and cleanup failures must both remain observable',
            self::InvalidGeneratedIntegration => 'The loaded generated assembly must expose exactly one supported web root and no operation roots',
        };
    }

    public function title(): string
    {
        return match ($this) {
            self::RuntimeRetired => 'Web runtime adapter is retired',
            self::DeliveryAndCleanupFailed => 'Web delivery and transport cleanup both failed',
            self::InvalidGeneratedIntegration => 'Invalid generated web-runtime integration',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::RuntimeRetired => 'Replace the retired runtime adapter before accepting more deliveries',
            self::DeliveryAndCleanupFailed => 'Retire the adapter and inspect both exact failure objects',
            self::InvalidGeneratedIntegration => 'Regenerate the exact supported web profile and supply its ordered typed startup inputs',
        };
    }
}
