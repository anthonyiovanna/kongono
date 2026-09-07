<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationContract\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

/** Owns the closed Safe diagnostic vocabulary for contract construction. */
#[Api(ApiStatus::Internal)]
enum ModelInvocationContractDiagnostic: string
{
    case InvalidIdentity = 'KGN-MODEL-CONTRACT-1401';
    case InvalidProfile = 'KGN-MODEL-CONTRACT-1402';
    case InvalidInput = 'KGN-MODEL-CONTRACT-1403';
    case InvalidRequest = 'KGN-MODEL-CONTRACT-1404';
    case InvalidResult = 'KGN-MODEL-CONTRACT-1405';
    case InvalidStreamEvent = 'KGN-MODEL-CONTRACT-1406';
    case InvalidUsageOrCostEvidence = 'KGN-MODEL-CONTRACT-1407';
    case InvalidReceipt = 'KGN-MODEL-CONTRACT-1408';
    case ContractLimitExceeded = 'KGN-MODEL-CONTRACT-1409';

    public function contextSchema(): DiagnosticContextSchema
    {
        return new DiagnosticContextSchema($this->operation(), $this->invariant(), [
            new DiagnosticFieldSchema('violation', 'Violation', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
            new DiagnosticFieldSchema('entry_count', 'Entry count', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false),
            new DiagnosticFieldSchema('entry_position', 'Entry position', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false),
            new DiagnosticFieldSchema('limit', 'Limit', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false),
            new DiagnosticFieldSchema('mode', 'Result mode', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe, false),
            new DiagnosticFieldSchema('event_kind', 'Event kind', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe, false),
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
            self::InvalidIdentity => 'Model invocation contract identity is invalid',
            self::InvalidProfile => 'Model profile reference is invalid',
            self::InvalidInput => 'Model invocation input is invalid',
            self::InvalidRequest => 'Model invocation request is invalid',
            self::InvalidResult => 'Normalized model result is invalid',
            self::InvalidStreamEvent => 'Normalized model stream event is invalid',
            self::InvalidUsageOrCostEvidence => 'Usage or cost evidence is invalid',
            self::InvalidReceipt => 'Model invocation receipt is invalid',
            self::ContractLimitExceeded => 'Model invocation contract limit is exceeded',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::InvalidIdentity => 'Supply a bounded lowercase ASCII identity matching the documented grammar',
            self::InvalidProfile => 'Supply one exact profile with a canonical nonempty capability set',
            self::InvalidInput => 'Supply bounded trimmed valid UTF-8 input with only allowed controls',
            self::InvalidRequest => 'Supply a typed canonical request supported by the exact profile capabilities',
            self::InvalidResult => 'Supply a normalized result whose mode, finish reason, and evidence shape agree',
            self::InvalidStreamEvent => 'Supply one typed stream event whose payload and usage match its kind',
            self::InvalidUsageOrCostEvidence => 'Supply canonical bounded usage or exact decimal cost evidence',
            self::InvalidReceipt => 'Supply a receipt whose attempt, outcome, result, and evidence relations agree',
            self::ContractLimitExceeded => 'Reduce the supplied value or list to the documented contract bound',
        };
    }

    private function operation(): string
    {
        return match ($this) {
            self::InvalidIdentity => 'Create model invocation identity value',
            self::InvalidProfile => 'Create model profile reference',
            self::InvalidInput => 'Create model invocation input value',
            self::InvalidRequest => 'Create model invocation request',
            self::InvalidResult => 'Create normalized model result',
            self::InvalidStreamEvent => 'Create normalized model stream event',
            self::InvalidUsageOrCostEvidence => 'Create usage or cost evidence',
            self::InvalidReceipt => 'Create model invocation receipt',
            self::ContractLimitExceeded => 'Validate model invocation contract bound',
        };
    }

    private function invariant(): string
    {
        return match ($this) {
            self::InvalidIdentity => 'Identity values must preserve exact bounded lowercase ASCII grammar',
            self::InvalidProfile => 'Profile capabilities must be typed, unique, canonical, and nonempty',
            self::InvalidInput => 'Input text must remain bounded, trimmed, valid, inert, and control-safe',
            self::InvalidRequest => 'Request shape and declared profile capabilities must agree exactly',
            self::InvalidResult => 'Result shape, finish reason, and optional evidence must agree exactly',
            self::InvalidStreamEvent => 'Stream event kind determines the exact payload and usage shape',
            self::InvalidUsageOrCostEvidence => 'Usage and cost claims must preserve bounded exact representations',
            self::InvalidReceipt => 'Receipt outcome, attempt, result, and evidence relations must agree exactly',
            self::ContractLimitExceeded => 'Every contract byte, integer, and list bound is fail-closed',
        };
    }
}
