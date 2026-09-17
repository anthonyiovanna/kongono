<?php

declare(strict_types=1);

namespace Kongono\CacheInspection\Graph;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\CacheInspection\Exception\InvalidCacheIntelligenceEvidenceException;
use Kongono\CacheInspection\Role\CacheInspectionDeveloperRole;

/** Carries optional Safe Graph aggregates while denying any relationship claim. */
#[Api(ApiStatus::Experimental)]
final readonly class CacheGraphEvidence
{
    public const string SUPPLIED = 'graph-evidence-supplied';
    public const string NOT_SUPPLIED = 'graph-evidence-not-supplied';
    public const string RELATIONSHIP = 'not-established';
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
        private CacheInspectionDeveloperRole $role,
        private bool $supplied,
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
            throw InvalidCacheIntelligenceEvidenceException::invalid('graph_evidence');
        }
        if (!$supplied) {
            if (null !== $contractVersion || 0 !== $nodeCount || 0 !== $relationCount || [] !== $nodeTypeCounts
                || [] !== $relationTypeCounts || null !== $scope || null !== $visibility || null !== $currentness
                || null !== $authority || [] !== $uncertainties) {
                throw InvalidCacheIntelligenceEvidenceException::invalid('graph_evidence');
            }
            $this->nodeTypeCounts = [];
            $this->relationTypeCounts = [];
            $this->uncertainties = [];

            return;
        }
        if (1 !== $contractVersion || $nodeCount < 0 || $nodeCount > 4096 || $relationCount < 0 || $relationCount > 16384
            || 'composition-v1' !== $scope || 'safe-aggregate-only' !== $visibility
            || 'not-established' !== $currentness || 'evidence-only' !== $authority
            || ['individual-facts-omitted', 'currentness-not-established', 'authorization-not-granted'] !== $uncertainties) {
            throw InvalidCacheIntelligenceEvidenceException::invalid('graph_evidence');
        }
        $this->nodeTypeCounts = $this->counts($nodeTypeCounts, self::NODE_KEYS, 4096);
        $this->relationTypeCounts = $this->counts($relationTypeCounts, self::RELATION_KEYS, 16384);
        if ($nodeCount !== array_sum($this->nodeTypeCounts) || $relationCount !== array_sum($this->relationTypeCounts)) {
            throw InvalidCacheIntelligenceEvidenceException::invalid('graph_evidence');
        }
        $this->uncertainties = [...$uncertainties];
    }

    public function inspectionIdentity(): string
    {
        return $this->inspectionIdentity;
    }

    public function role(): CacheInspectionDeveloperRole
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
                'contract_version' => $this->contractVersion,
                'node_count' => $this->nodeCount,
                'relation_count' => $this->relationCount,
                'node_type_counts' => $this->nodeTypeCounts,
                'relation_type_counts' => $this->relationTypeCounts,
                'scope' => $this->scope,
                'visibility' => $this->visibility,
                'currentness' => $this->currentness,
                'authority' => $this->authority,
                'uncertainties' => $this->uncertainties,
            ];
        }

        return [
            'schema' => 'kongono.cache-graph-evidence',
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
    private function counts(array $counts, array $keys, int $limit): array
    {
        if ($keys !== array_keys($counts)) {
            throw InvalidCacheIntelligenceEvidenceException::invalid('graph_evidence');
        }
        $accepted = [];
        foreach ($keys as $key) {
            if (!is_int($counts[$key]) || $counts[$key] < 0 || $counts[$key] > $limit) {
                throw InvalidCacheIntelligenceEvidenceException::invalid('graph_evidence');
            }
            $accepted[$key] = $counts[$key];
        }

        return $accepted;
    }
}
