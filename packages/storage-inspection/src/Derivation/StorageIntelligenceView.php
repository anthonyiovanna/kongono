<?php

declare(strict_types=1);

namespace Kongono\StorageInspection\Derivation;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\StorageInspection\Exception\InvalidStorageIntelligenceEvidenceException;
use Kongono\StorageInspection\Graph\StorageGraphEvidence;
use Kongono\StorageInspection\Inspection\StorageInspection;
use Kongono\StorageInspection\Internal\CanonicalJson;
use Kongono\StorageInspection\Role\StorageInspectionDeveloperRole;

/** Carries one presentation-neutral role-addressed storage evidence view. */
#[Api(ApiStatus::Experimental)]
final readonly class StorageIntelligenceView
{
    public const array STATEMENTS = [
        'storage-profile-set-reconstructed-in-memory',
        'storage-profile-artifact-bytes-revalidated-in-memory',
        'source-build-artifact-bindings-coherent',
        'runtime-evidence-is-optional-caller-supplied-detached-aggregate',
        'object-identities-content-and-live-provider-state-omitted',
        'no-storage-operation-or-production-observation-performed',
    ];
    public const array LIMITATIONS = [
        'live-provider-runtime-and-currentness-not-established',
        'storage-correctness-durability-security-and-availability-not-certified',
        'graph-relationship-not-established',
        'role-execution-repair-apply-recording-exporting-and-certification-not-performed',
    ];

    private string $serializationSha256;
    /** @var list<string> */
    private array $statements;
    /** @var list<string> */
    private array $limitations;

    /**
     * @param array<array-key, mixed> $statements
     * @param array<array-key, mixed> $limitations
     */
    public function __construct(
        private StorageInspectionDeveloperRole $role,
        private string $purpose,
        private StorageInspection $inspection,
        private StorageGraphEvidence $graphEvidence,
        array $statements = self::STATEMENTS,
        array $limitations = self::LIMITATIONS,
        private string $roleExecution = 'not-performed',
        private string $repair = 'not-performed',
        private string $apply = 'not-performed',
        private string $hiddenDataAccess = 'none',
        private string $filesystemAccess = 'not-performed',
        private string $providerAccess = 'not-performed',
        private string $adapterAccess = 'not-performed',
        private string $credentialValidation = 'not-performed',
        private string $authorizationExecution = 'not-performed',
        private string $runtimeAccess = 'not-performed',
        private string $clockRead = 'not-performed',
        private string $mutation = 'not-performed',
        private string $networkAccess = 'not-performed',
        private string $recording = 'not-performed',
        private string $exporting = 'not-performed',
        private string $productionObservation = 'not-performed',
        private string $certification = 'not-performed',
        private string $authority = 'evidence-only',
        ?string $serializationSha256 = null,
    ) {
        if ($purpose !== $role->purpose() || $graphEvidence->role() !== $role
            || !hash_equals($inspection->identity(), $graphEvidence->inspectionIdentity())) {
            throw InvalidStorageIntelligenceEvidenceException::invalid('role_evidence');
        }
        foreach ([$roleExecution, $repair, $apply, $filesystemAccess, $providerAccess, $adapterAccess, $credentialValidation, $authorizationExecution, $runtimeAccess, $clockRead, $mutation, $networkAccess, $recording, $exporting, $productionObservation, $certification] as $marker) {
            if ('not-performed' !== $marker) {
                throw InvalidStorageIntelligenceEvidenceException::invalid('role_evidence');
            }
        }
        if ('none' !== $hiddenDataAccess || 'evidence-only' !== $authority) {
            throw InvalidStorageIntelligenceEvidenceException::invalid('role_evidence');
        }
        $this->statements = $this->closedList($statements, self::STATEMENTS);
        $this->limitations = $this->closedList($limitations, self::LIMITATIONS);
        $expected = hash('sha256', CanonicalJson::encode($this->evidencePayload()));
        if (null !== $serializationSha256 && (1 !== preg_match('/\A[a-f0-9]{64}\z/D', $serializationSha256) || !hash_equals($expected, $serializationSha256))) {
            throw InvalidStorageIntelligenceEvidenceException::invalid('role_evidence');
        }
        $this->serializationSha256 = $serializationSha256 ?? $expected;
    }

    public function role(): StorageInspectionDeveloperRole
    {
        return $this->role;
    }
    public function purpose(): string
    {
        return $this->purpose;
    }
    public function inspection(): StorageInspection
    {
        return $this->inspection;
    }
    public function graphEvidence(): StorageGraphEvidence
    {
        return $this->graphEvidence;
    }
    /** @return list<string> */
    public function statements(): array
    {
        return [...$this->statements];
    }
    /** @return list<string> */
    public function limitations(): array
    {
        return [...$this->limitations];
    }
    public function serializationSha256(): string
    {
        return $this->serializationSha256;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return $this->evidencePayload() + ['serialization_sha256' => $this->serializationSha256];
    }

    public function canonicalBytes(): string
    {
        return CanonicalJson::encode($this->toArray());
    }

    /** @return array<string, mixed> */
    private function evidencePayload(): array
    {
        return [
            'schema' => 'kongono.storage-intelligence-view',
            'version' => 1,
            'role' => $this->role->value,
            'purpose' => $this->purpose,
            'inspection' => $this->inspection->toArray(),
            'graph_evidence' => $this->graphEvidence->toArray(),
            'statements' => $this->statements,
            'limitations' => $this->limitations,
            'role_execution' => $this->roleExecution,
            'repair' => $this->repair,
            'apply' => $this->apply,
            'hidden_data_access' => $this->hiddenDataAccess,
            'filesystem_access' => $this->filesystemAccess,
            'provider_access' => $this->providerAccess,
            'adapter_access' => $this->adapterAccess,
            'credential_validation' => $this->credentialValidation,
            'authorization_execution' => $this->authorizationExecution,
            'runtime_access' => $this->runtimeAccess,
            'clock_read' => $this->clockRead,
            'mutation' => $this->mutation,
            'network_access' => $this->networkAccess,
            'recording' => $this->recording,
            'exporting' => $this->exporting,
            'production_observation' => $this->productionObservation,
            'certification' => $this->certification,
            'authority' => $this->authority,
        ];
    }

    /**
     * @param array<array-key, mixed> $values
     * @param list<string> $allowed
     * @return list<string>
     */
    private function closedList(array $values, array $allowed): array
    {
        if ($values !== $allowed) {
            throw InvalidStorageIntelligenceEvidenceException::invalid('role_evidence');
        }

        return [...$allowed];
    }
}
