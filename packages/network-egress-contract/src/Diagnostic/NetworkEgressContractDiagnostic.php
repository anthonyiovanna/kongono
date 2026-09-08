<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressContract\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

/** Owns the closed Safe diagnostic vocabulary for contract construction. */
#[Api(ApiStatus::Internal)]
enum NetworkEgressContractDiagnostic: string
{
    case InvalidIdentity = 'KGN-EGRESS-CONTRACT-1501';
    case InvalidOrigin = 'KGN-EGRESS-CONTRACT-1502';
    case InvalidReference = 'KGN-EGRESS-CONTRACT-1503';
    case InvalidBudget = 'KGN-EGRESS-CONTRACT-1504';
    case InvalidCapabilityEvidence = 'KGN-EGRESS-CONTRACT-1505';
    case InvalidOperation = 'KGN-EGRESS-CONTRACT-1506';
    case InvalidOutcomeOrReceipt = 'KGN-EGRESS-CONTRACT-1507';
    case ContractLimitExceeded = 'KGN-EGRESS-CONTRACT-1508';

    public function contextSchema(): DiagnosticContextSchema
    {
        return new DiagnosticContextSchema($this->operation(), $this->invariant(), [
            new DiagnosticFieldSchema('violation', 'Violation', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
            new DiagnosticFieldSchema('entry_count', 'Entry count', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false),
            new DiagnosticFieldSchema('entry_position', 'Entry position', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false),
            new DiagnosticFieldSchema('limit', 'Limit', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false),
            new DiagnosticFieldSchema('outcome', 'Outcome', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe, false),
            new DiagnosticFieldSchema('state', 'Transfer state', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe, false),
        ]);
    }

    /** @param array<string, string|int|bool> $values */
    public function context(array $values): DiagnosticContext
    {
        return new DiagnosticContext($this->contextSchema(), $values);
    }

    public function title(): string
    {
        return match ($this) {
            self::InvalidIdentity => 'Network egress contract identity is invalid',
            self::InvalidOrigin => 'Canonical network origin is invalid',
            self::InvalidReference => 'Network egress reference is invalid',
            self::InvalidBudget => 'Network egress budget is invalid',
            self::InvalidCapabilityEvidence => 'Transport capability evidence is invalid',
            self::InvalidOperation => 'Network egress operation is invalid',
            self::InvalidOutcomeOrReceipt => 'Network egress outcome or receipt is invalid',
            self::ContractLimitExceeded => 'Network egress contract limit is exceeded',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::InvalidIdentity => 'Supply a bounded lowercase ASCII identity matching the documented grammar',
            self::InvalidOrigin => 'Supply an exact canonical lowercase HTTP or HTTPS origin',
            self::InvalidReference => 'Supply a bounded reference with its required revision or digest',
            self::InvalidBudget => 'Supply every finite budget within the documented bounds and timeout relations',
            self::InvalidCapabilityEvidence => 'Supply a nonempty unique capability list in canonical order',
            self::InvalidOperation => 'Supply a PSR-7 request whose absolute URI matches the declared origin',
            self::InvalidOutcomeOrReceipt => 'Supply coherent typed transfer, cancellation, outcome, and receipt evidence',
            self::ContractLimitExceeded => 'Reduce the supplied value or list to the documented contract bound',
        };
    }

    private function operation(): string
    {
        return match ($this) {
            self::InvalidIdentity => 'Create network egress identity value',
            self::InvalidOrigin => 'Create canonical network origin',
            self::InvalidReference => 'Create network egress authority or policy reference',
            self::InvalidBudget => 'Create finite network egress budget',
            self::InvalidCapabilityEvidence => 'Create transport capability evidence',
            self::InvalidOperation => 'Create neutral network egress operation',
            self::InvalidOutcomeOrReceipt => 'Create network egress outcome or receipt evidence',
            self::ContractLimitExceeded => 'Validate network egress contract bound',
        };
    }

    private function invariant(): string
    {
        return match ($this) {
            self::InvalidIdentity => 'Identity values must preserve exact bounded lowercase ASCII grammar',
            self::InvalidOrigin => 'Origins must contain only canonical scheme host and effective port evidence',
            self::InvalidReference => 'References must remain bounded exact inert evidence rather than authority',
            self::InvalidBudget => 'Every declared network egress budget must be finite and internally coherent',
            self::InvalidCapabilityEvidence => 'Capabilities must be typed unique bounded and canonically ordered',
            self::InvalidOperation => 'Request URI evidence and the explicit canonical origin must agree exactly',
            self::InvalidOutcomeOrReceipt => 'Terminal outcome attempt transfer cancellation and response relations must agree',
            self::ContractLimitExceeded => 'Every contract byte integer and list bound is fail closed',
        };
    }
}
