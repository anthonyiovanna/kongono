<?php

declare(strict_types=1);

namespace Kongono\DatabaseApplication\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

/** Owns the closed non-disclosing database-application diagnostic contract. */
#[Api(ApiStatus::Internal)]
enum DatabaseApplicationDiagnostic: string
{
    case SessionUnavailable = 'KGN-DATABASE-APPLICATION-1001';
    case InvalidGeneratedProfile = 'KGN-DATABASE-APPLICATION-1002';
    case WorkflowFailed = 'KGN-DATABASE-APPLICATION-1003';
    case LifecycleFailed = 'KGN-DATABASE-APPLICATION-1004';

    public function context(string $failureCategory): DiagnosticContext
    {
        return new DiagnosticContext(new DiagnosticContextSchema(
            $this->operation(),
            $this->violatedInvariant(),
            [
                new DiagnosticFieldSchema('schema_version', 'Schema version', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe),
                new DiagnosticFieldSchema('failure_category', 'Failure category', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
            ],
        ), [
            'schema_version' => 1,
            'failure_category' => $failureCategory,
        ]);
    }

    public function operation(): string
    {
        return match ($this) {
            self::SessionUnavailable => 'Use operation database session',
            self::InvalidGeneratedProfile => 'Validate generated data-application profile',
            self::WorkflowFailed => 'Build or check generated data application',
            self::LifecycleFailed => 'Manage generated data-operation lifecycle',
        };
    }

    public function violatedInvariant(): string
    {
        return match ($this) {
            self::SessionUnavailable => 'An operation session is unavailable from the instant close begins',
            self::InvalidGeneratedProfile => 'The generated assembly must expose exactly one typed operation root and no application roots',
            self::WorkflowFailed => 'Mapping and composition evidence must be exact, current, ordered, contained, and unchanged',
            self::LifecycleFailed => 'Application and operation wrappers must reject use after retirement',
        };
    }

    public function title(): string
    {
        return match ($this) {
            self::SessionUnavailable => 'Operation database session unavailable',
            self::InvalidGeneratedProfile => 'Invalid generated data-application profile',
            self::WorkflowFailed => 'Data-application workflow failed',
            self::LifecycleFailed => 'Generated data-application lifecycle failed',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::SessionUnavailable => 'Begin a fresh generated operation with a fresh connector session',
            self::InvalidGeneratedProfile => 'Correct the application composition source and rebuild exact generated wiring',
            self::WorkflowFailed => 'Restore exact current build inputs and rerun the explicit build or check workflow',
            self::LifecycleFailed => 'Close active work and begin a fresh application or operation boundary',
        };
    }
}
