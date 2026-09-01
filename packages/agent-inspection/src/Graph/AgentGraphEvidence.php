<?php

declare(strict_types=1);

namespace Kongono\AgentInspection\Graph;

use Kongono\AgentInspection\Exception\InvalidAgentIntelligenceEvidenceException;
use Kongono\AgentInspection\Role\DeveloperIntelligenceRole;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Carries optional bounded Graph aggregates while denying any agent relationship claim. */
#[Api(ApiStatus::Experimental)]
final readonly class AgentGraphEvidence
{
    public const string SUPPLIED = 'graph-evidence-supplied';
    public const string NOT_SUPPLIED = 'graph-evidence-not-supplied';
    public const string RELATIONSHIP = 'agent-graph-relationship-not-established';
    private const array NODE_KEYS = ['composition.input', 'composition.service', 'composition.contract', 'composition.root'];
    private const array RELATION_KEYS = ['composition.depends_on', 'composition.consumes_input', 'composition.implements_contract', 'composition.exposes_root'];

    /** @var array<string, int> */
    private array $nodeTypeCounts;
    /** @var array<string, int> */
    private array $relationTypeCounts;
    /** @var list<string> */
    private array $uncertainties;

    /**
     * @param array<array-key, mixed> $nodeTypeCounts
     * @param array<array-key, mixed> $relationTypeCounts
     * @param array<array-key, mixed> $uncertainties
     */
    public function __construct(
        private string $inspectionIdentity,
        private DeveloperIntelligenceRole $role,
        private bool $supplied,
        private ?string $artifactSha256 = null,
        private ?string $graphIdentity = null,
        private ?int $contractVersion = null,
        private int $nodeCount = 0,
        private int $relationCount = 0,
        array $nodeTypeCounts = [],
        array $relationTypeCounts = [],
        private ?string $scope = null,
        private ?string $visibility = null,
        private ?string $currentness = null,
        private ?string $authority = null,
        array $uncertainties = [],
        private string $relationship = self::RELATIONSHIP,
    ) {
        if (1 !== preg_match('/\A[a-f0-9]{64}\z/D', $inspectionIdentity) || self::RELATIONSHIP !== $relationship) {
            throw InvalidAgentIntelligenceEvidenceException::invalid('graph_evidence');
        }
        if (!$supplied) {
            if (null !== $artifactSha256 || null !== $graphIdentity || null !== $contractVersion || 0 !== $nodeCount || 0 !== $relationCount
                || [] !== $nodeTypeCounts || [] !== $relationTypeCounts || null !== $scope || null !== $visibility || null !== $currentness || null !== $authority || [] !== $uncertainties) {
                throw InvalidAgentIntelligenceEvidenceException::invalid('graph_evidence');
            }
            $this->nodeTypeCounts = [];
            $this->relationTypeCounts = [];
            $this->uncertainties = [];

            return;
        }
        if (null === $artifactSha256 || 1 !== preg_match('/\A[a-f0-9]{64}\z/D', $artifactSha256)
            || null === $graphIdentity || 1 !== preg_match('/\Akgn:graph:v1:graph:[a-f0-9]{64}\z/D', $graphIdentity) || null === $contractVersion || $contractVersion < 1
            || $nodeCount < 0 || $relationCount < 0
            || 'composition-v1' !== $scope || 'safe-aggregate-only' !== $visibility || 'not-established' !== $currentness || 'evidence-only' !== $authority
            || ['individual-facts-omitted', 'currentness-not-established', 'authorization-not-granted'] !== $uncertainties) {
            throw InvalidAgentIntelligenceEvidenceException::invalid('graph_evidence');
        }
        $this->nodeTypeCounts = $this->counts($nodeTypeCounts, self::NODE_KEYS);
        $this->relationTypeCounts = $this->counts($relationTypeCounts, self::RELATION_KEYS);
        if ($nodeCount !== array_sum($this->nodeTypeCounts) || $relationCount !== array_sum($this->relationTypeCounts)) {
            throw InvalidAgentIntelligenceEvidenceException::invalid('graph_evidence');
        }
        $this->uncertainties = [...$uncertainties];
    }

    public function inspectionIdentity(): string
    {
        return $this->inspectionIdentity;
    }
    public function role(): DeveloperIntelligenceRole
    {
        return $this->role;
    }
    public function supplied(): bool
    {
        return $this->supplied;
    }
    public function relationship(): string
    {
        return $this->relationship;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $graph = [];
        if ($this->supplied) {
            $graph = [
                'artifact_sha256' => $this->artifactSha256,
                'graph_identity' => $this->graphIdentity,
                'contract_version' => $this->contractVersion,
                'node_count' => $this->nodeCount,
                'relation_count' => $this->relationCount,
                'node_type_counts' => [...$this->nodeTypeCounts],
                'relation_type_counts' => [...$this->relationTypeCounts],
                'scope' => $this->scope,
                'visibility' => $this->visibility,
                'currentness' => $this->currentness,
                'authority' => $this->authority,
                'uncertainties' => [...$this->uncertainties],
            ];
        }

        return [
            'schema' => 'kongono.agent-graph-evidence',
            'version' => 1,
            'inspection_identity' => $this->inspectionIdentity,
            'role' => $this->role->value,
            'status' => $this->supplied ? self::SUPPLIED : self::NOT_SUPPLIED,
            'graph' => $graph,
            'relationship' => $this->relationship,
        ];
    }

    /**
     * @param array<array-key, mixed> $counts
     * @param list<string> $keys
     * @return array<string, int>
     */
    private function counts(array $counts, array $keys): array
    {
        if ($keys !== array_keys($counts)) {
            throw InvalidAgentIntelligenceEvidenceException::invalid('graph_evidence');
        }
        $accepted = [];
        foreach ($keys as $key) {
            if (!is_int($counts[$key]) || $counts[$key] < 0) {
                throw InvalidAgentIntelligenceEvidenceException::invalid('graph_evidence');
            }
            $accepted[$key] = $counts[$key];
        }

        return $accepted;
    }
}
