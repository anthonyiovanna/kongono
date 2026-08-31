<?php

declare(strict_types=1);

namespace Kongono\ExtensionContract\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

/** Owns fixed non-disclosing metadata for extension contract failures. */
#[Api(ApiStatus::Internal)]
enum ExtensionContractDiagnostic: string
{
    case InvalidIdentity = 'KGN-EXTENSION-1201';
    case InvalidVersion = 'KGN-EXTENSION-1202';
    case InvalidProvenance = 'KGN-EXTENSION-1203';
    case InvalidCapabilityRequest = 'KGN-EXTENSION-1204';
    case InvalidContribution = 'KGN-EXTENSION-1205';
    case InvalidDeclaration = 'KGN-EXTENSION-1206';
    case ContractLimitExceeded = 'KGN-EXTENSION-1207';

    public function contextSchema(): DiagnosticContextSchema
    {
        $fields = [new DiagnosticFieldSchema('violation', 'Violation', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe)];
        if (self::InvalidDeclaration === $this || self::ContractLimitExceeded === $this) {
            $fields[] = new DiagnosticFieldSchema('entry_count', 'Entry count', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false);
            $fields[] = new DiagnosticFieldSchema('entry_position', 'Entry position', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false);
        }

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
            self::InvalidIdentity => 'Extension contract identity is invalid',
            self::InvalidVersion => 'Extension contract version is invalid',
            self::InvalidProvenance => 'Extension contract provenance is invalid',
            self::InvalidCapabilityRequest => 'Extension capability request is invalid',
            self::InvalidContribution => 'Extension contribution is invalid',
            self::InvalidDeclaration => 'Extension declaration is invalid',
            self::ContractLimitExceeded => 'Extension contract limit is exceeded',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::InvalidIdentity => 'Supply an exact bounded lowercase ASCII identity',
            self::InvalidVersion => 'Supply an exact bounded opaque or integer version',
            self::InvalidProvenance => 'Supply only exact lowercase SHA-256 source evidence',
            self::InvalidCapabilityRequest => 'Supply a locally valid inert capability request',
            self::InvalidContribution => 'Supply locally valid bounded contribution metadata',
            self::InvalidDeclaration => 'Supply exact typed canonical unique declaration lists',
            self::ContractLimitExceeded => 'Reduce the supplied canonical list to its documented bound',
        };
    }

    private function operation(): string
    {
        return match ($this) {
            self::InvalidIdentity => 'Create extension contract identity',
            self::InvalidVersion => 'Create extension contract version',
            self::InvalidProvenance => 'Create extension provenance',
            self::InvalidCapabilityRequest => 'Create capability request',
            self::InvalidContribution => 'Create contribution declaration',
            self::InvalidDeclaration => 'Create extension declaration',
            self::ContractLimitExceeded => 'Validate extension contract bound',
        };
    }

    private function invariant(): string
    {
        return match ($this) {
            self::InvalidIdentity => 'Identity must satisfy its exact lowercase ASCII grammar and bound',
            self::InvalidVersion => 'Version must satisfy its exact grammar or integer bound',
            self::InvalidProvenance => 'Provenance must contain only exact bounded non-secret source evidence',
            self::InvalidCapabilityRequest => 'Capability request fields must satisfy their local contract',
            self::InvalidContribution => 'Contribution fields must satisfy their local contract',
            self::InvalidDeclaration => 'Declaration lists must be typed, canonical, unique, and ordered',
            self::ContractLimitExceeded => 'Declaration list size must remain within its documented maximum',
        };
    }
}
