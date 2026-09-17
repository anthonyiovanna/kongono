<?php

declare(strict_types=1);

namespace Kongono\CacheApplication\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

/** Owns the closed nondisclosing Phase 20F diagnostic vocabulary. */
#[Api(ApiStatus::Internal)]
enum CacheApplicationDiagnostic: string
{
    case InvalidDefinition = 'KGN-CACHE-APPLICATION-2041';
    case UnsafeSource = 'KGN-CACHE-APPLICATION-2042';
    case WorkflowFailed = 'KGN-CACHE-APPLICATION-2043';
    case PublicationFailed = 'KGN-CACHE-APPLICATION-2044';
    case ProfileCodecMismatch = 'KGN-CACHE-APPLICATION-2045';
    case RuntimeCompositionFailed = 'KGN-CACHE-APPLICATION-2046';
    case OperationMismatch = 'KGN-CACHE-APPLICATION-2047';
    case InternalInvariant = 'KGN-CACHE-APPLICATION-2048';

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
            self::InvalidDefinition => 'Cache Application definition is invalid',
            self::UnsafeSource => 'Cache Application source evidence is unsafe or unstable',
            self::WorkflowFailed => 'Cache Application workflow or currentness check failed',
            self::PublicationFailed => 'Cache Application publication failed',
            self::ProfileCodecMismatch => 'Cache Application profile and codec mapping do not match',
            self::RuntimeCompositionFailed => 'Cache Application runtime composition failed',
            self::OperationMismatch => 'Cache Application operation does not match its evidence',
            self::InternalInvariant => 'Cache Application internal invariant failed',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::InvalidDefinition => 'Supply one closed Version 1 definition and canonical contained paths',
            self::UnsafeSource => 'Supply one unchanged ordinary bounded source file beneath the application root',
            self::WorkflowFailed => 'Correct the source or exact content-addressed current evidence',
            self::PublicationFailed => 'Supply a writable contained publication root without conflicting content',
            self::ProfileCodecMismatch => 'Supply an existing profile and its exact declared codec identity',
            self::RuntimeCompositionFailed => 'Supply exact-current evidence and one matching adapter identity',
            self::OperationMismatch => 'Use the matching explicit operation and evidence identity',
            self::InternalInvariant => 'Report the bounded invariant identifier to the framework maintainer',
        };
    }

    private function operation(): string
    {
        return 'Compose explicit Cache Application evidence';
    }
    private function invariant(): string
    {
        return 'Cache application evidence must remain bounded explicit exact-current and nondisclosing';
    }
}
