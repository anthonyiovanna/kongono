<?php

declare(strict_types=1);

namespace Kongono\StorageContract\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

/** Owns the closed Safe diagnostic vocabulary for neutral storage contracts. */
#[Api(ApiStatus::Internal)]
enum StorageContractDiagnostic: string
{
    case InvalidIdentity = 'KGN-STORAGE-CONTRACT-2101';
    case InvalidCapability = 'KGN-STORAGE-CONTRACT-2102';
    case InvalidContentEvidence = 'KGN-STORAGE-CONTRACT-2103';
    case InvalidMutationCondition = 'KGN-STORAGE-CONTRACT-2104';
    case InvalidRequest = 'KGN-STORAGE-CONTRACT-2105';
    case InvalidResult = 'KGN-STORAGE-CONTRACT-2106';
    case InvalidStreamContract = 'KGN-STORAGE-CONTRACT-2107';
    case ContractBoundExceeded = 'KGN-STORAGE-CONTRACT-2108';

    public function contextSchema(): DiagnosticContextSchema
    {
        return new DiagnosticContextSchema($this->operation(), $this->invariant(), [
            new DiagnosticFieldSchema('violation', 'Violation', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
            new DiagnosticFieldSchema('operation', 'Operation', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe, false),
            new DiagnosticFieldSchema('outcome', 'Outcome', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe, false),
            new DiagnosticFieldSchema('capability', 'Capability', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe, false),
            new DiagnosticFieldSchema('digest_algorithm', 'Digest algorithm', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe, false),
            new DiagnosticFieldSchema('entry_count', 'Entry count', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false),
            new DiagnosticFieldSchema('entry_position', 'Entry position', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false),
            new DiagnosticFieldSchema('limit', 'Limit', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false),
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
            self::InvalidIdentity => 'Storage contract identity is invalid',
            self::InvalidCapability => 'Storage capability evidence is invalid',
            self::InvalidContentEvidence => 'Storage content evidence is invalid',
            self::InvalidMutationCondition => 'Storage mutation condition is invalid',
            self::InvalidRequest => 'Storage request is invalid',
            self::InvalidResult => 'Storage result evidence is invalid',
            self::InvalidStreamContract => 'Storage stream lifecycle contract is invalid',
            self::ContractBoundExceeded => 'Storage contract bound is exceeded',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::InvalidIdentity => 'Supply the documented bounded canonical logical identity',
            self::InvalidCapability => 'Supply only the closed declared storage capabilities as a list',
            self::InvalidContentEvidence => 'Supply bounded content, digest, metadata, version, or continuation evidence',
            self::InvalidMutationCondition => 'Use if_absent for create or exact version evidence for replace and delete',
            self::InvalidRequest => 'Supply a Version 1 request whose operation and fields satisfy the closed relation',
            self::InvalidResult => 'Supply detached evidence allowed by the exact outcome relation',
            self::InvalidStreamContract => 'Honor the documented bounded read, write, close, and abort lifecycle',
            self::ContractBoundExceeded => 'Reduce the supplied value or collection to the documented finite limit',
        };
    }

    private function operation(): string
    {
        return match ($this) {
            self::InvalidIdentity => 'Create storage identity evidence',
            self::InvalidCapability => 'Create storage capability evidence',
            self::InvalidContentEvidence => 'Create storage content evidence',
            self::InvalidMutationCondition => 'Create storage mutation condition',
            self::InvalidRequest => 'Create storage operation request',
            self::InvalidResult => 'Create storage result evidence',
            self::InvalidStreamContract => 'Use storage stream lifecycle',
            self::ContractBoundExceeded => 'Validate storage contract bound',
        };
    }

    private function invariant(): string
    {
        return match ($this) {
            self::InvalidIdentity => 'Logical identity domains remain canonical, bounded, and separate from provider location',
            self::InvalidCapability => 'Capability declarations use only the closed support vocabulary and grant no authority',
            self::InvalidContentEvidence => 'Content and metadata evidence is bounded, canonical, detached, and nondisclosing',
            self::InvalidMutationCondition => 'Every mutation is create-only or bound to exact accepted version evidence',
            self::InvalidRequest => 'Requests preserve exact operation and cross-field relations without runtime authority',
            self::InvalidResult => 'Results make only evidence claims permitted by their exact closed outcome',
            self::InvalidStreamContract => 'Streaming remains bounded and uses explicit close or abort lifecycle control',
            self::ContractBoundExceeded => 'Every identity, byte, metadata, and pagination dimension is finite before an effect',
        };
    }
}
