<?php

declare(strict_types=1);

namespace Kongono\AgentPrincipalContract\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

/** Owns fixed non-disclosing metadata for Agent Principal contract failures. */
#[Api(ApiStatus::Internal)]
enum AgentPrincipalContractDiagnostic: string
{
    case InvalidPrincipal = 'KGN-AGENT-CONTRACT-1301';
    case InvalidIdentity = 'KGN-AGENT-CONTRACT-1302';
    case InvalidPurpose = 'KGN-AGENT-CONTRACT-1303';
    case InvalidEvidence = 'KGN-AGENT-CONTRACT-1304';
    case InvalidProposal = 'KGN-AGENT-CONTRACT-1305';
    case ContractLimitExceeded = 'KGN-AGENT-CONTRACT-1306';
    case InvalidReceipt = 'KGN-AGENT-CONTRACT-1307';

    public function contextSchema(): DiagnosticContextSchema
    {
        $fields = [new DiagnosticFieldSchema('violation', 'Violation', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe)];
        if (self::InvalidProposal === $this || self::ContractLimitExceeded === $this) {
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
            self::InvalidPrincipal => 'Agent Principal reference is invalid',
            self::InvalidIdentity => 'Agent Principal contract identity is invalid',
            self::InvalidPurpose => 'Agent Principal contract purpose is invalid',
            self::InvalidEvidence => 'Agent Principal evidence reference is invalid',
            self::InvalidProposal => 'Agent action proposal is invalid',
            self::ContractLimitExceeded => 'Agent Principal contract limit is exceeded',
            self::InvalidReceipt => 'Agent Principal receipt is invalid',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::InvalidPrincipal => 'Supply an exact Principal reference whose kind is Agent',
            self::InvalidIdentity => 'Supply an exact bounded lowercase ASCII identity or digest',
            self::InvalidPurpose => 'Supply bounded trimmed inert UTF-8 purpose text',
            self::InvalidEvidence => 'Supply exact bounded evidence or provenance references',
            self::InvalidProposal => 'Supply exact typed canonical unique proposal values',
            self::ContractLimitExceeded => 'Reduce the supplied canonical list to its documented bound',
            self::InvalidReceipt => 'Supply an exact bounded receipt relation and evidence digest',
        };
    }

    private function operation(): string
    {
        return match ($this) {
            self::InvalidPrincipal => 'Create Agent Principal reference',
            self::InvalidIdentity => 'Create Agent Principal contract identity',
            self::InvalidPurpose => 'Create Agent Principal contract purpose',
            self::InvalidEvidence => 'Create Agent Principal evidence reference',
            self::InvalidProposal => 'Create Agent action proposal',
            self::ContractLimitExceeded => 'Validate Agent Principal contract bound',
            self::InvalidReceipt => 'Create Agent Principal receipt',
        };
    }

    private function invariant(): string
    {
        return match ($this) {
            self::InvalidPrincipal => 'Only an exact Agent-kind Principal reference may be wrapped',
            self::InvalidIdentity => 'Identities and digests must satisfy their exact grammar and byte bound',
            self::InvalidPurpose => 'Purpose text must remain bounded, trimmed, valid, inert, and control-free',
            self::InvalidEvidence => 'Evidence and provenance must remain bounded external references',
            self::InvalidProposal => 'Proposal values and lists must be typed, canonical, unique, and ordered',
            self::ContractLimitExceeded => 'Proposal list size must remain within its documented maximum',
            self::InvalidReceipt => 'Receipt relations and evidence digest must satisfy the exact local contract',
        };
    }
}
