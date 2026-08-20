<?php

declare(strict_types=1);

namespace Kongono\Web\Application\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;

/** Owns fixed package-safe metadata for Web Application lifecycle failures. */
#[Api(ApiStatus::Internal)]
enum WebApplicationDiagnostic: string
{
    case ApplicationRetired = 'KGN-WEB-APPLICATION-1001';
    case OperationAndCleanupFailed = 'KGN-WEB-APPLICATION-1002';

    public function context(): DiagnosticContext
    {
        return new DiagnosticContext(new DiagnosticContextSchema(
            $this->contextOperation(),
            $this->contextViolatedInvariant(),
            $this->contextFields(),
        ));
    }

    /** @return list<DiagnosticFieldSchema> */
    public function contextFields(): array
    {
        return [];
    }

    public function contextOperation(): string
    {
        return match ($this) {
            self::ApplicationRetired => 'Use web application lifecycle',
            self::OperationAndCleanupFailed => 'Complete web application operation',
        };
    }

    public function contextViolatedInvariant(): string
    {
        return match ($this) {
            self::ApplicationRetired => 'A retired application must not accept handling or shutdown work',
            self::OperationAndCleanupFailed => 'Primary and cleanup failures must both remain observable',
        };
    }

    public function title(): string
    {
        return match ($this) {
            self::ApplicationRetired => 'Web application is retired',
            self::OperationAndCleanupFailed => 'Application work and operation cleanup both failed',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::ApplicationRetired => 'Replace the retired application instance before accepting more work',
            self::OperationAndCleanupFailed => 'Retire the application instance and inspect both failure objects',
        };
    }
}
