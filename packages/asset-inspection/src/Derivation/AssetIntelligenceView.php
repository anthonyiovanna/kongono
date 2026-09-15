<?php

declare(strict_types=1);

namespace Kongono\AssetInspection\Derivation;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetInspection\Exception\InvalidAssetIntelligenceEvidenceException;
use Kongono\AssetInspection\Graph\AssetGraphEvidence;
use Kongono\AssetInspection\Inspection\AssetInspection;
use Kongono\AssetInspection\Internal\CanonicalJson;
use Kongono\AssetInspection\Role\AssetInspectionDeveloperRole;

/** Carries one presentation-neutral role-addressed evidence view. */
#[Api(ApiStatus::Experimental)]
final readonly class AssetIntelligenceView
{
    public const array STATEMENTS = [
        'resolution-artifact-structure-reconstructed-in-memory',
        'current-result-count-bindings-coherent',
        'currentness-evidence-is-caller-supplied',
        'logical-identities-paths-urls-bytes-and-object-keys-omitted',
        'no-build-resolution-or-production-observation-performed',
    ];
    public const array LIMITATIONS = [
        'live-filesystem-currentness-not-established',
        'deployment-serving-browser-security-accessibility-and-performance-not-established',
        'graph-relationship-not-established',
        'role-execution-repair-recording-exporting-and-certification-not-performed',
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
        private AssetInspectionDeveloperRole $role,
        private string $purpose,
        private AssetInspection $inspection,
        private AssetGraphEvidence $graphEvidence,
        array $statements = self::STATEMENTS,
        array $limitations = self::LIMITATIONS,
        private string $roleExecution = 'not-performed',
        private string $repair = 'not-performed',
        private string $hiddenDataAccess = 'none',
        private string $filesystemAccess = 'not-performed',
        private string $sourceOutputReading = 'not-performed',
        private string $building = 'not-performed',
        private string $resolution = 'not-performed',
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
            throw InvalidAssetIntelligenceEvidenceException::invalid('role_evidence');
        }
        foreach ([$roleExecution, $repair, $filesystemAccess, $sourceOutputReading, $building, $resolution, $networkAccess, $recording, $exporting, $productionObservation, $certification] as $marker) {
            if ('not-performed' !== $marker) {
                throw InvalidAssetIntelligenceEvidenceException::invalid('role_evidence');
            }
        }
        if ('none' !== $hiddenDataAccess || 'evidence-only' !== $authority) {
            throw InvalidAssetIntelligenceEvidenceException::invalid('role_evidence');
        }
        $this->statements = $this->closedList($statements, self::STATEMENTS);
        $this->limitations = $this->closedList($limitations, self::LIMITATIONS);
        $expected = hash('sha256', CanonicalJson::encode($this->evidencePayload()));
        if (null !== $serializationSha256 && (1 !== preg_match('/\A[a-f0-9]{64}\z/D', $serializationSha256) || !hash_equals($expected, $serializationSha256))) {
            throw InvalidAssetIntelligenceEvidenceException::invalid('role_evidence');
        }
        $this->serializationSha256 = $serializationSha256 ?? $expected;
    }

    public function role(): AssetInspectionDeveloperRole
    {
        return $this->role;
    }
    public function purpose(): string
    {
        return $this->purpose;
    }
    public function inspection(): AssetInspection
    {
        return $this->inspection;
    }
    public function graphEvidence(): AssetGraphEvidence
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
            'schema' => 'kongono.asset-intelligence-view',
            'version' => 1,
            'role' => $this->role->value,
            'purpose' => $this->purpose,
            'inspection' => $this->inspection->toArray(),
            'graph_evidence' => $this->graphEvidence->toArray(),
            'statements' => $this->statements,
            'limitations' => $this->limitations,
            'role_execution' => $this->roleExecution,
            'repair' => $this->repair,
            'hidden_data_access' => $this->hiddenDataAccess,
            'filesystem_access' => $this->filesystemAccess,
            'source_output_reading' => $this->sourceOutputReading,
            'building' => $this->building,
            'resolution' => $this->resolution,
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
            throw InvalidAssetIntelligenceEvidenceException::invalid('role_evidence');
        }

        return [...$allowed];
    }
}
