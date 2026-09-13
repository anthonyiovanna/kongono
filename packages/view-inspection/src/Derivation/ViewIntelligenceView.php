<?php

declare(strict_types=1);

namespace Kongono\ViewInspection\Derivation;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ViewInspection\Exception\InvalidViewIntelligenceEvidenceException;
use Kongono\ViewInspection\Graph\ViewGraphEvidence;
use Kongono\ViewInspection\Inspection\ViewInspection;
use Kongono\ViewInspection\Internal\CanonicalJson;
use Kongono\ViewInspection\Role\ViewInspectionDeveloperRole;

/** Carries one presentation-neutral role-addressed view without executing a role. */
#[Api(ApiStatus::Experimental)]
final readonly class ViewIntelligenceView
{
    public const array STATEMENTS = [
        'artifact-structure-validated-in-memory',
        'profile-and-artifact-bindings-coherent',
        'exact-current-evidence-is-caller-supplied',
        'generated-php-not-executed',
        'rendering-and-production-observation-not-performed',
    ];
    public const array LIMITATIONS = [
        'live-filesystem-currentness-not-established',
        'graph-relationship-not-established',
        'role-execution-and-repair-not-performed',
        'recording-exporting-and-certification-not-performed',
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
        private ViewInspectionDeveloperRole $role,
        private string $purpose,
        private ViewInspection $inspection,
        private ViewGraphEvidence $graphEvidence,
        array $statements = self::STATEMENTS,
        array $limitations = self::LIMITATIONS,
        private string $roleExecution = 'not-performed',
        private string $repair = 'not-performed',
        private string $hiddenDataAccess = 'none',
        private string $filesystemAccess = 'not-performed',
        private string $generatedPhpExecution = 'not-performed',
        private string $rendering = 'not-performed',
        private string $productionObservation = 'not-performed',
        private string $recording = 'not-performed',
        private string $exporting = 'not-performed',
        private string $certification = 'not-performed',
        private string $authority = 'evidence-only',
        ?string $serializationSha256 = null,
    ) {
        if (!in_array($purpose, ['explain', 'verify', 'teach'], true)
            || !in_array($roleExecution, ['not-performed', 'not-observed'], true)
            || !in_array($repair, ['not-performed', 'not-observed'], true)
            || !in_array($hiddenDataAccess, ['none', 'not-established'], true)
            || !in_array($filesystemAccess, ['not-performed', 'not-observed'], true)
            || !in_array($generatedPhpExecution, ['not-performed', 'not-observed'], true)
            || !in_array($rendering, ['not-performed', 'not-observed'], true)
            || !in_array($productionObservation, ['not-performed', 'not-observed'], true)
            || !in_array($recording, ['not-performed', 'not-observed'], true)
            || !in_array($exporting, ['not-performed', 'not-observed'], true)
            || !in_array($certification, ['not-performed', 'not-observed'], true)
            || !in_array($authority, ['evidence-only', 'not-evaluated'], true)
            || $graphEvidence->role() !== $role
            || !hash_equals($inspection->identity(), $graphEvidence->inspectionIdentity())) {
            throw InvalidViewIntelligenceEvidenceException::invalid('role_evidence');
        }
        $this->statements = $this->closedList($statements, self::STATEMENTS);
        $this->limitations = $this->closedList($limitations, self::LIMITATIONS);
        $expected = hash('sha256', CanonicalJson::encode($this->evidencePayload()));
        if (null !== $serializationSha256 && 1 !== preg_match('/\A[a-f0-9]{64}\z/D', $serializationSha256)) {
            throw InvalidViewIntelligenceEvidenceException::invalid('role_evidence');
        }
        $this->serializationSha256 = $serializationSha256 ?? $expected;
    }

    public function role(): ViewInspectionDeveloperRole
    {
        return $this->role;
    }

    public function purpose(): string
    {
        return $this->purpose;
    }

    public function inspection(): ViewInspection
    {
        return $this->inspection;
    }

    public function graphEvidence(): ViewGraphEvidence
    {
        return $this->graphEvidence;
    }

    /** @return list<string> */
    public function statements(): array
    {
        return $this->statements;
    }

    /** @return list<string> */
    public function limitations(): array
    {
        return $this->limitations;
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

    public function filesystemAccess(): string
    {
        return $this->filesystemAccess;
    }

    public function generatedPhpExecution(): string
    {
        return $this->generatedPhpExecution;
    }

    public function rendering(): string
    {
        return $this->rendering;
    }

    public function productionObservation(): string
    {
        return $this->productionObservation;
    }

    public function recording(): string
    {
        return $this->recording;
    }

    public function exporting(): string
    {
        return $this->exporting;
    }

    public function certification(): string
    {
        return $this->certification;
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
            'schema' => 'kongono.view-intelligence-view',
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
            'generated_php_execution' => $this->generatedPhpExecution,
            'rendering' => $this->rendering,
            'production_observation' => $this->productionObservation,
            'recording' => $this->recording,
            'exporting' => $this->exporting,
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
        if (!array_is_list($values) || count($values) !== count($allowed)) {
            throw InvalidViewIntelligenceEvidenceException::invalid('role_evidence');
        }
        foreach ($values as $value) {
            if (!is_string($value) || !in_array($value, $allowed, true)) {
                throw InvalidViewIntelligenceEvidenceException::invalid('role_evidence');
            }
        }
        if (count(array_unique($values)) !== count($allowed)) {
            throw InvalidViewIntelligenceEvidenceException::invalid('role_evidence');
        }

        /** @var list<string> $values */
        return [...$values];
    }
}
