<?php

declare(strict_types=1);

namespace Kongono\AssetContract\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

/** Owns the closed Safe diagnostic vocabulary for asset contracts. */
#[Api(ApiStatus::Internal)]
enum AssetContractDiagnostic: string
{
    case InvalidIdentity = 'KGN-ASSET-CONTRACT-1901';
    case InvalidDeclaration = 'KGN-ASSET-CONTRACT-1902';
    case InvalidDependency = 'KGN-ASSET-CONTRACT-1903';
    case InvalidEntryPoint = 'KGN-ASSET-CONTRACT-1904';
    case InvalidDeliveryMetadata = 'KGN-ASSET-CONTRACT-1905';
    case ContractBoundExceeded = 'KGN-ASSET-CONTRACT-1906';

    public function contextSchema(): DiagnosticContextSchema
    {
        return new DiagnosticContextSchema($this->operation(), $this->invariant(), [
            new DiagnosticFieldSchema('violation', 'Violation', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
            new DiagnosticFieldSchema('entry_count', 'Entry count', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false),
            new DiagnosticFieldSchema('entry_position', 'Entry position', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false),
            new DiagnosticFieldSchema('limit', 'Limit', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false),
            new DiagnosticFieldSchema('asset_kind', 'Asset kind', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe, false),
            new DiagnosticFieldSchema('dependency_kind', 'Dependency kind', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe, false),
            new DiagnosticFieldSchema('algorithm', 'Algorithm', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe, false),
            new DiagnosticFieldSchema('preload_hint', 'Preload hint', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe, false),
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
            self::InvalidIdentity => 'Asset contract identity is invalid',
            self::InvalidDeclaration => 'Asset declaration is invalid',
            self::InvalidDependency => 'Asset dependency is invalid',
            self::InvalidEntryPoint => 'Asset entry point is invalid',
            self::InvalidDeliveryMetadata => 'Asset delivery metadata is invalid',
            self::ContractBoundExceeded => 'Asset contract bound is exceeded',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::InvalidIdentity => 'Supply the documented bounded canonical asset identity',
            self::InvalidDeclaration => 'Supply unique closed declarations with matching contract versions and content types',
            self::InvalidDependency => 'Supply unique non-self relations whose endpoints are declared',
            self::InvalidEntryPoint => 'Supply unique entry points whose logical assets are declared',
            self::InvalidDeliveryMetadata => 'Supply bounded delivery evidence whose closed relations agree',
            self::ContractBoundExceeded => 'Reduce the supplied bytes or collection to the documented bound',
        };
    }

    private function operation(): string
    {
        return match ($this) {
            self::InvalidIdentity => 'Create asset contract identity',
            self::InvalidDeclaration => 'Create asset declaration evidence',
            self::InvalidDependency => 'Create asset dependency evidence',
            self::InvalidEntryPoint => 'Create asset entry-point evidence',
            self::InvalidDeliveryMetadata => 'Create asset delivery metadata',
            self::ContractBoundExceeded => 'Validate asset contract bound',
        };
    }

    private function invariant(): string
    {
        return match ($this) {
            self::InvalidIdentity => 'Logical and content identities must preserve their exact canonical grammar',
            self::InvalidDeclaration => 'Declarations must remain closed, unique, version-consistent, and canonical',
            self::InvalidDependency => 'Dependencies must remain non-self, unique, declared, and version-consistent',
            self::InvalidEntryPoint => 'Entry points must remain unique, declared, and version-consistent',
            self::InvalidDeliveryMetadata => 'Delivery evidence must preserve exact kind, content, integrity, byte, and hint relations',
            self::ContractBoundExceeded => 'Every caller-supplied byte string and collection bound is fail-closed',
        };
    }
}
