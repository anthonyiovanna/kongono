<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphIntelligence\Derivation;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ApplicationGraphArtifact\Identity\GraphIdentity;
use Kongono\ApplicationGraphContract\ContractVersion;
use Kongono\ApplicationGraphContract\Vocabulary\NodeType;
use Kongono\ApplicationGraphContract\Vocabulary\RelationType;
use Kongono\ApplicationGraphIntelligence\Exception\InvalidApplicationGraphIntelligenceValueException;
use Kongono\ApplicationGraphIntelligence\Role\DeveloperIntelligenceRole;

/** Carries one detached Safe aggregate Graph view for a selected role audience. */
#[Api(ApiStatus::Experimental)]
final readonly class ApplicationGraphIntelligenceView
{
    public const string SCOPE = 'composition-v1';
    public const string VISIBILITY = 'safe-aggregate-only';
    public const string CURRENTNESS = 'not-established';
    public const string AUTHORITY = 'evidence-only';

    /** @var list<string> */
    public const array UNCERTAINTIES = [
        'individual-facts-omitted',
        'currentness-not-established',
        'authorization-not-granted',
    ];

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
        private DeveloperIntelligenceRole $role,
        private string $artifactSha256,
        private GraphIdentity $graphIdentity,
        private ContractVersion $contractVersion,
        private int $nodeCount,
        private int $relationCount,
        array $nodeTypeCounts,
        array $relationTypeCounts,
        private string $scope = self::SCOPE,
        private string $visibility = self::VISIBILITY,
        private string $currentness = self::CURRENTNESS,
        private string $authority = self::AUTHORITY,
        array $uncertainties = self::UNCERTAINTIES,
    ) {
        if (1 !== preg_match('/\A[a-f0-9]{64}\z/D', $artifactSha256)) {
            throw InvalidApplicationGraphIntelligenceValueException::view('artifact_digest');
        }

        if ($nodeCount < 0 || $relationCount < 0) {
            throw InvalidApplicationGraphIntelligenceValueException::view('negative_total');
        }

        $this->nodeTypeCounts = self::normalizeCounts(
            $nodeTypeCounts,
            array_map(static fn(NodeType $type): string => $type->value, NodeType::cases()),
            'node_vocabulary',
        );
        $this->relationTypeCounts = self::normalizeCounts(
            $relationTypeCounts,
            array_map(static fn(RelationType $type): string => $type->value, RelationType::cases()),
            'relation_vocabulary',
        );

        if ($nodeCount !== array_sum($this->nodeTypeCounts)) {
            throw InvalidApplicationGraphIntelligenceValueException::view('node_total');
        }
        if ($relationCount !== array_sum($this->relationTypeCounts)) {
            throw InvalidApplicationGraphIntelligenceValueException::view('relation_total');
        }
        if (
            self::SCOPE !== $scope
            || self::VISIBILITY !== $visibility
            || self::CURRENTNESS !== $currentness
            || self::AUTHORITY !== $authority
        ) {
            throw InvalidApplicationGraphIntelligenceValueException::view('semantic_token');
        }
        if (self::UNCERTAINTIES !== $uncertainties) {
            throw InvalidApplicationGraphIntelligenceValueException::view('uncertainty_order');
        }

        $this->uncertainties = [...self::UNCERTAINTIES];
    }

    public function role(): DeveloperIntelligenceRole
    {
        return $this->role;
    }

    public function artifactSha256(): string
    {
        return $this->artifactSha256;
    }

    public function graphIdentity(): GraphIdentity
    {
        return $this->graphIdentity;
    }

    public function contractVersion(): ContractVersion
    {
        return $this->contractVersion;
    }

    public function nodeCount(): int
    {
        return $this->nodeCount;
    }

    public function relationCount(): int
    {
        return $this->relationCount;
    }

    public function nodeTypeCount(NodeType $type): int
    {
        return $this->nodeTypeCounts[$type->value];
    }

    public function relationTypeCount(RelationType $type): int
    {
        return $this->relationTypeCounts[$type->value];
    }

    public function scope(): string
    {
        return $this->scope;
    }

    public function visibility(): string
    {
        return $this->visibility;
    }

    public function currentness(): string
    {
        return $this->currentness;
    }

    public function authority(): string
    {
        return $this->authority;
    }

    /** @return list<string> */
    public function uncertainties(): array
    {
        return [...$this->uncertainties];
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'schema' => 'kongono.application-graph-intelligence-view',
            'version' => 1,
            'role' => $this->role->value,
            'artifact_sha256' => $this->artifactSha256,
            'graph_identity' => $this->graphIdentity->value(),
            'graph_contract_version' => $this->contractVersion->value,
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

    /**
     * @param array<array-key, mixed> $counts
     * @param list<string> $expectedKeys
     * @return array<string, int>
     */
    private static function normalizeCounts(array $counts, array $expectedKeys, string $component): array
    {
        if ($expectedKeys !== array_keys($counts)) {
            throw InvalidApplicationGraphIntelligenceValueException::view($component);
        }

        $normalized = [];
        foreach ($expectedKeys as $key) {
            $count = $counts[$key];
            if (!is_int($count)) {
                throw InvalidApplicationGraphIntelligenceValueException::view('serialization');
            }
            if ($count < 0) {
                throw InvalidApplicationGraphIntelligenceValueException::view('negative_count');
            }
            $normalized[$key] = $count;
        }

        return $normalized;
    }
}
