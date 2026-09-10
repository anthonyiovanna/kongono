<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceContract\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

/** Owns the closed Safe diagnostic vocabulary for contract construction. */
#[Api(ApiStatus::Internal)]
enum ExternalServiceContractDiagnostic: string
{
    case InvalidIdentity = 'KGN-EXTERNAL-SERVICE-CONTRACT-1601';
    case InvalidEndpoint = 'KGN-EXTERNAL-SERVICE-CONTRACT-1602';
    case InvalidBinding = 'KGN-EXTERNAL-SERVICE-CONTRACT-1603';
    case InvalidCredentialReference = 'KGN-EXTERNAL-SERVICE-CONTRACT-1604';
    case InvalidCredentialLease = 'KGN-EXTERNAL-SERVICE-CONTRACT-1605';
    case InvalidLifecycle = 'KGN-EXTERNAL-SERVICE-CONTRACT-1606';
    case InvalidResult = 'KGN-EXTERNAL-SERVICE-CONTRACT-1607';
    case ContractLimitExceeded = 'KGN-EXTERNAL-SERVICE-CONTRACT-1608';

    public function contextSchema(): DiagnosticContextSchema
    {
        return new DiagnosticContextSchema($this->operation(), $this->invariant(), [
            new DiagnosticFieldSchema('violation', 'Violation', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
            new DiagnosticFieldSchema('entry_count', 'Entry count', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false),
            new DiagnosticFieldSchema('entry_position', 'Entry position', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false),
            new DiagnosticFieldSchema('limit', 'Limit', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false),
            new DiagnosticFieldSchema('state', 'State', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe, false),
            new DiagnosticFieldSchema('outcome', 'Outcome', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe, false),
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
            self::InvalidIdentity => 'External service contract identity is invalid',
            self::InvalidEndpoint => 'External service endpoint is invalid',
            self::InvalidBinding => 'External service binding is invalid',
            self::InvalidCredentialReference => 'Credential reference is invalid',
            self::InvalidCredentialLease => 'Credential lease evidence is invalid',
            self::InvalidLifecycle => 'External service lifecycle is invalid',
            self::InvalidResult => 'External service operation result is invalid',
            self::ContractLimitExceeded => 'External service contract limit is exceeded',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::InvalidIdentity => 'Supply a bounded lowercase ASCII identity matching the documented grammar',
            self::InvalidEndpoint => 'Supply an empty or canonical bounded absolute endpoint base path',
            self::InvalidBinding => 'Supply coherent typed secret-free service-binding evidence',
            self::InvalidCredentialReference => 'Supply a bounded secret-free source identity and opaque canonical reference',
            self::InvalidCredentialLease => 'Supply a purpose-bound lease request with a finite documented lifetime',
            self::InvalidLifecycle => 'Supply one of the documented terminal lifecycle states',
            self::InvalidResult => 'Supply a terminal lifecycle outcome and failure combination that satisfies the documented relations',
            self::ContractLimitExceeded => 'Reduce the supplied value or count to the documented contract bound',
        };
    }

    private function operation(): string
    {
        return match ($this) {
            self::InvalidIdentity => 'Create external service identity value',
            self::InvalidEndpoint => 'Create external service endpoint evidence',
            self::InvalidBinding => 'Create external service binding evidence',
            self::InvalidCredentialReference => 'Create credential reference evidence',
            self::InvalidCredentialLease => 'Create credential lease request evidence',
            self::InvalidLifecycle => 'Create external service lifecycle evidence',
            self::InvalidResult => 'Create external service operation result evidence',
            self::ContractLimitExceeded => 'Validate external service contract bound',
        };
    }

    private function invariant(): string
    {
        return match ($this) {
            self::InvalidIdentity => 'Identities and purposes must preserve exact bounded lowercase ASCII grammar',
            self::InvalidEndpoint => 'Endpoint base paths must be canonical bounded absolute ASCII paths or empty',
            self::InvalidBinding => 'Bindings must remain typed immutable secret-free evidence',
            self::InvalidCredentialReference => 'Credential references must remain bounded opaque evidence without secret material',
            self::InvalidCredentialLease => 'Lease requests must bind one operation reference purpose and finite lifetime',
            self::InvalidLifecycle => 'Operation results may expose only a documented terminal lifecycle state',
            self::InvalidResult => 'Terminal outcome lifecycle and failure evidence must agree',
            self::ContractLimitExceeded => 'Every contract byte integer and list bound is fail closed',
        };
    }
}
