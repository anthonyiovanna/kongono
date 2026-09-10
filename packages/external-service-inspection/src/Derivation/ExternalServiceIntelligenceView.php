<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceInspection\Derivation;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExternalServiceInspection\Exception\InvalidExternalServiceIntelligenceEvidenceException;
use Kongono\ExternalServiceInspection\Graph\ExternalServiceGraphEvidence;
use Kongono\ExternalServiceInspection\Inspection\ExternalServiceInspection;
use Kongono\ExternalServiceInspection\Internal\CanonicalJson;
use Kongono\ExternalServiceInspection\Role\ExternalServiceInspectionDeveloperRole;

/** Carries one bounded role-addressed evidence view without executing a developer role. */
#[Api(ApiStatus::Experimental)]
final readonly class ExternalServiceIntelligenceView
{
    /** @var list<string> */
    private array $evidenceGroups;

    /** @var list<string> */
    private array $statements;

    private string $serializationSha256;

    /**
     * @param list<string> $evidenceGroups
     * @param list<string> $statements
     */
    public function __construct(
        private ExternalServiceInspectionDeveloperRole $role,
        private string $purpose,
        private ExternalServiceInspection $inspection,
        private ExternalServiceGraphEvidence $graphEvidence,
        array $evidenceGroups,
        array $statements,
        private string $roleExecution = 'not-performed',
        private string $credentialAccess = 'not-performed',
        private string $networkAccess = 'not-performed',
        private string $authorityEvaluation = 'not-performed',
        private string $repair = 'not-performed',
        private string $replay = 'not-performed',
        private string $certification = 'not-performed',
        private string $hiddenDataAccess = 'none',
        private string $authority = 'evidence-only',
        ?string $serializationSha256 = null,
    ) {
        if ($role->purpose() !== $purpose
            || ['inspection-bindings', 'optional-completed-result-evidence', 'graph-aggregates', 'limitations'] !== $evidenceGroups
            || self::statementsFor($role) !== $statements
            || 'not-performed' !== $roleExecution
            || 'not-performed' !== $credentialAccess
            || 'not-performed' !== $networkAccess
            || 'not-performed' !== $authorityEvaluation
            || 'not-performed' !== $repair
            || 'not-performed' !== $replay
            || 'not-performed' !== $certification
            || 'none' !== $hiddenDataAccess
            || 'evidence-only' !== $authority) {
            throw InvalidExternalServiceIntelligenceEvidenceException::invalid('role_evidence');
        }
        $this->evidenceGroups = [...$evidenceGroups];
        $this->statements = [...$statements];
        $expected = hash('sha256', CanonicalJson::encode($this->payload()));
        if (null !== $serializationSha256 && 1 !== preg_match('/\A[a-f0-9]{64}\z/D', $serializationSha256)) {
            throw InvalidExternalServiceIntelligenceEvidenceException::invalid('role_evidence');
        }
        $this->serializationSha256 = $serializationSha256 ?? $expected;
    }

    public function role(): ExternalServiceInspectionDeveloperRole
    {
        return $this->role;
    }

    public function purpose(): string
    {
        return $this->purpose;
    }

    public function inspection(): ExternalServiceInspection
    {
        return $this->inspection;
    }

    public function graphEvidence(): ExternalServiceGraphEvidence
    {
        return $this->graphEvidence;
    }

    /** @return list<string> */
    public function evidenceGroups(): array
    {
        return [...$this->evidenceGroups];
    }

    /** @return list<string> */
    public function statements(): array
    {
        return [...$this->statements];
    }

    public function roleExecution(): string
    {
        return $this->roleExecution;
    }

    public function networkAccess(): string
    {
        return $this->networkAccess;
    }

    public function credentialAccess(): string
    {
        return $this->credentialAccess;
    }

    public function authorityEvaluation(): string
    {
        return $this->authorityEvaluation;
    }

    public function repair(): string
    {
        return $this->repair;
    }

    public function replay(): string
    {
        return $this->replay;
    }

    public function certification(): string
    {
        return $this->certification;
    }

    public function hiddenDataAccess(): string
    {
        return $this->hiddenDataAccess;
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

    /** @return list<string> */
    public static function statementsFor(ExternalServiceInspectionDeveloperRole $role): array
    {
        return match ($role) {
            ExternalServiceInspectionDeveloperRole::Chie => ['bounded-inspection-evidence-explained', 'limitations-remain-explicit'],
            ExternalServiceInspectionDeveloperRole::Hantei => ['exact-evidence-compared', 'mismatch-fails-closed'],
            ExternalServiceInspectionDeveloperRole::Oshie => ['inspection-boundary-taught', 'uncertainty-semantics-preserved'],
        };
    }

    /** @return array<string, mixed> */
    private function payload(): array
    {
        return [
            'schema' => 'kongono.external-service-intelligence-view',
            'version' => 1,
            'role' => $this->role->value,
            'purpose' => $this->purpose,
            'evidence_groups' => [...$this->evidenceGroups],
            'statements' => [...$this->statements],
            'inspection' => $this->inspection->toArray(),
            'graph_evidence' => $this->graphEvidence->toArray(),
            'role_execution' => $this->roleExecution,
            'credential_access' => $this->credentialAccess,
            'network_access' => $this->networkAccess,
            'authority_evaluation' => $this->authorityEvaluation,
            'repair' => $this->repair,
            'replay' => $this->replay,
            'certification' => $this->certification,
            'hidden_data_access' => $this->hiddenDataAccess,
            'authority' => $this->authority,
        ];
    }
}
