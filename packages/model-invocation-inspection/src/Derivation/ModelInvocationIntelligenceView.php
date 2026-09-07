<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationInspection\Derivation;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationInspection\Exception\InvalidModelInvocationIntelligenceEvidenceException;
use Kongono\ModelInvocationInspection\Graph\ModelInvocationGraphEvidence;
use Kongono\ModelInvocationInspection\Inspection\ModelInvocationInspection;
use Kongono\ModelInvocationInspection\Internal\CanonicalJson;
use Kongono\ModelInvocationInspection\Role\ModelInspectionDeveloperRole;

/** Carries one presentation-neutral role-addressed view without executing a role. */
#[Api(ApiStatus::Experimental)]
final readonly class ModelInvocationIntelligenceView
{
    private string $serializationSha256;

    public function __construct(
        private ModelInspectionDeveloperRole $role,
        private string $purpose,
        private ModelInvocationInspection $inspection,
        private ModelInvocationGraphEvidence $graphEvidence,
        private string $roleExecution = 'not-performed',
        private string $repair = 'not-performed',
        private string $hiddenDataAccess = 'none',
        private string $providerInvocation = 'not-performed',
        private string $authority = 'evidence-only',
        ?string $serializationSha256 = null,
    ) {
        if (!in_array($purpose, ['explain', 'verify', 'teach'], true)
            || !in_array($roleExecution, ['not-performed', 'not-observed'], true)
            || !in_array($repair, ['not-performed', 'not-observed'], true)
            || !in_array($hiddenDataAccess, ['none', 'not-established'], true)
            || !in_array($providerInvocation, ['not-performed', 'not-observed'], true)
            || !in_array($authority, ['evidence-only', 'not-established'], true)) {
            throw InvalidModelInvocationIntelligenceEvidenceException::invalid('role_evidence');
        }
        $expected = hash('sha256', CanonicalJson::encode($this->payload()));
        if (null !== $serializationSha256 && 1 !== preg_match('/\A[a-f0-9]{64}\z/D', $serializationSha256)) {
            throw InvalidModelInvocationIntelligenceEvidenceException::invalid('role_evidence');
        }
        $this->serializationSha256 = $serializationSha256 ?? $expected;
    }

    public function role(): ModelInspectionDeveloperRole
    {
        return $this->role;
    }
    public function purpose(): string
    {
        return $this->purpose;
    }
    public function inspection(): ModelInvocationInspection
    {
        return $this->inspection;
    }
    public function graphEvidence(): ModelInvocationGraphEvidence
    {
        return $this->graphEvidence;
    }
    public function roleExecution(): string
    {
        return $this->roleExecution;
    }
    public function repair(): string
    {
        return $this->repair;
    }
    public function hiddenDataAccess(): string
    {
        return $this->hiddenDataAccess;
    }
    public function providerInvocation(): string
    {
        return $this->providerInvocation;
    }
    public function authority(): string
    {
        return $this->authority;
    }
    public function serializationSha256(): string
    {
        return $this->serializationSha256;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return $this->payload() + ['serialization_sha256' => $this->serializationSha256];
    }

    public function canonicalBytes(): string
    {
        return CanonicalJson::encode($this->toArray());
    }

    /** @return array<string, mixed> */
    private function payload(): array
    {
        return [
            'schema' => 'kongono.model-invocation-intelligence-view',
            'version' => 1,
            'role' => $this->role->value,
            'purpose' => $this->purpose,
            'inspection' => $this->inspection->toArray(),
            'graph_evidence' => $this->graphEvidence->toArray(),
            'role_execution' => $this->roleExecution,
            'repair' => $this->repair,
            'hidden_data_access' => $this->hiddenDataAccess,
            'provider_invocation' => $this->providerInvocation,
            'authority' => $this->authority,
        ];
    }
}
