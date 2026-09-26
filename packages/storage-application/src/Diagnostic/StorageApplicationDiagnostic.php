<?php

declare(strict_types=1);

namespace Kongono\StorageApplication\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

/** Owns the closed nondisclosing Phase 21F diagnostic vocabulary. */
#[Api(ApiStatus::Internal)]
enum StorageApplicationDiagnostic: string
{
    case InvalidDefinition = 'KGN-STORAGE-APPLICATION-2141';
    case UnsafeSource = 'KGN-STORAGE-APPLICATION-2142';
    case WorkflowFailed = 'KGN-STORAGE-APPLICATION-2143';
    case RuntimeCompositionFailed = 'KGN-STORAGE-APPLICATION-2144';
    case AuthorizationDenied = 'KGN-STORAGE-APPLICATION-2145';
    case AuthorizationFailure = 'KGN-STORAGE-APPLICATION-2146';
    case InternalInvariant = 'KGN-STORAGE-APPLICATION-2147';
    case PublicationFailed = 'KGN-STORAGE-APPLICATION-2148';

    public function context(string $value): DiagnosticContext
    {
        return new DiagnosticContext(
            new DiagnosticContextSchema(
                $this->operation(),
                $this->invariant(),
                [new DiagnosticFieldSchema('stage', 'Stage', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe)],
            ),
            ['stage' => $value],
        );
    }

    public function title(): string
    {
        return match ($this) {
            self::InvalidDefinition => 'Storage Application definition is invalid',
            self::UnsafeSource => 'Storage Application source evidence is unsafe or unstable',
            self::WorkflowFailed => 'Storage Application workflow or currentness check failed',
            self::PublicationFailed => 'Storage Application publication failed',
            self::RuntimeCompositionFailed => 'Storage Application runtime composition failed',
            self::AuthorizationDenied => 'Storage Application authorization was denied',
            self::AuthorizationFailure => 'Storage Application authorization collaborator failed',
            self::InternalInvariant => 'Storage Application internal invariant failed',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::InvalidDefinition => 'Supply one closed Version 1 definition and canonical contained paths',
            self::UnsafeSource => 'Supply one unchanged ordinary bounded source file beneath the application root',
            self::WorkflowFailed => 'Correct the source or exact content-addressed current evidence',
            self::PublicationFailed => 'Supply a writable contained publication root without conflicting content',
            self::RuntimeCompositionFailed => 'Supply exact-current evidence and one matching adapter identity',
            self::AuthorizationDenied => 'Supply an authorized operation under application policy',
            self::AuthorizationFailure => 'Correct the application authorization collaborator',
            self::InternalInvariant => 'Report the bounded invariant identifier to the framework maintainer',
        };
    }

    private function operation(): string
    {
        return 'Compose explicit Storage Application evidence';
    }
    private function invariant(): string
    {
        return 'Storage application evidence must remain bounded explicit exact-current and nondisclosing';
    }
}
