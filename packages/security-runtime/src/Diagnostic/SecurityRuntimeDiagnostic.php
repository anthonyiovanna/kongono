<?php

declare(strict_types=1);

namespace Kongono\SecurityRuntime\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

/** Owns the closed non-disclosing Security Runtime diagnostic contract. */
#[Api(ApiStatus::Internal)]
enum SecurityRuntimeDiagnostic: string
{
    case InvalidVerification = 'KGN-SECURITY-RUNTIME-1001';
    case InvalidGeneratedProfile = 'KGN-SECURITY-RUNTIME-1002';
    case LifecycleUnavailable = 'KGN-SECURITY-RUNTIME-1003';

    public function context(string $failureCategory): DiagnosticContext
    {
        return new DiagnosticContext(new DiagnosticContextSchema(
            $this->operation(),
            $this->violatedInvariant(),
            [
                new DiagnosticFieldSchema('package', 'Package', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
                new DiagnosticFieldSchema('failure_category', 'Failure category', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
            ],
        ), [
            'package' => 'security_runtime',
            'failure_category' => $failureCategory,
        ]);
    }

    public function operation(): string
    {
        return match ($this) {
            self::InvalidVerification => 'Construct authenticated Security Context',
            self::InvalidGeneratedProfile => 'Validate generated security profile',
            self::LifecycleUnavailable => 'Use generated security lifecycle',
        };
    }

    public function violatedInvariant(): string
    {
        return match ($this) {
            self::InvalidVerification => 'Authenticated context construction requires exact successful and internally consistent verification evidence',
            self::InvalidGeneratedProfile => 'The loaded version-2 application must expose one Security Context operation input and one typed operation root',
            self::LifecycleUnavailable => 'Retired applications and closed operations must reject further access',
        };
    }

    public function title(): string
    {
        return match ($this) {
            self::InvalidVerification => 'Invalid Security Context verification',
            self::InvalidGeneratedProfile => 'Invalid generated security profile',
            self::LifecycleUnavailable => 'Security Runtime lifecycle unavailable',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::InvalidVerification => 'Supply one exact authenticated verification or explicitly begin an anonymous operation',
            self::InvalidGeneratedProfile => 'Correct the version-2 composition source and rebuild exact generated wiring',
            self::LifecycleUnavailable => 'Begin a fresh application or operation boundary after completing active cleanup',
        };
    }
}
