<?php

declare(strict_types=1);

namespace Kongono\AgentInspection\Derivation;

use Kongono\AgentInspection\Exception\InvalidAgentIntelligenceEvidenceException;
use Kongono\AgentInspection\Graph\AgentGraphEvidence;
use Kongono\AgentInspection\Inspection\AgentPrincipalInspection;
use Kongono\AgentInspection\Internal\CanonicalJson;
use Kongono\AgentInspection\Role\DeveloperIntelligenceRole;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Carries one role-addressed view of only authorized bounded evidence. */
#[Api(ApiStatus::Experimental)]
final readonly class AgentIntelligenceView
{
    private string $serializationSha256;

    public function __construct(
        private DeveloperIntelligenceRole $role,
        private string $purpose,
        private AgentPrincipalInspection $inspection,
        private AgentGraphEvidence $graphEvidence,
        private string $roleExecution = 'not-performed',
        private string $repair = 'not-performed',
        private string $authority = 'evidence-only',
        private string $hiddenDataAccess = 'none',
        ?string $serializationSha256 = null,
    ) {
        if (!in_array($purpose, ['explain', 'verify', 'teach'], true)
            || !in_array($roleExecution, ['not-performed', 'not-observed'], true)
            || !in_array($repair, ['not-performed', 'not-observed'], true)
            || !in_array($authority, ['evidence-only', 'not-established'], true)
            || !in_array($hiddenDataAccess, ['none', 'not-established'], true)) {
            throw InvalidAgentIntelligenceEvidenceException::invalid('role_evidence');
        }
        $expected = hash('sha256', CanonicalJson::encode($this->payload()));
        if (null !== $serializationSha256 && 1 !== preg_match('/\A[a-f0-9]{64}\z/D', $serializationSha256)) {
            throw InvalidAgentIntelligenceEvidenceException::invalid('role_evidence');
        }
        $this->serializationSha256 = $serializationSha256 ?? $expected;
    }

    public function role(): DeveloperIntelligenceRole
    {
        return $this->role;
    }
    public function purpose(): string
    {
        return $this->purpose;
    }
    public function inspection(): AgentPrincipalInspection
    {
        return $this->inspection;
    }
    public function graphEvidence(): AgentGraphEvidence
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
    public function authority(): string
    {
        return $this->authority;
    }
    public function hiddenDataAccess(): string
    {
        return $this->hiddenDataAccess;
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

    /** @return array<string, mixed> */
    private function payload(): array
    {
        return [
            'schema' => 'kongono.agent-intelligence-view',
            'version' => 1,
            'role' => $this->role->value,
            'purpose' => $this->purpose,
            'inspection' => $this->inspection->toArray(),
            'graph_evidence' => $this->graphEvidence->toArray(),
            'role_execution' => $this->roleExecution,
            'repair' => $this->repair,
            'authority' => $this->authority,
            'hidden_data_access' => $this->hiddenDataAccess,
        ];
    }
}
