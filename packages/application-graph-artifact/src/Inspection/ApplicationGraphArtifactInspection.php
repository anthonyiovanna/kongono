<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphArtifact\Inspection;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ApplicationGraphArtifact\Exception\InvalidApplicationGraphArtifactException;
use Kongono\ApplicationGraphArtifact\Identity\GraphIdentity;
use Kongono\ApplicationGraphContract\ContractVersion;
use Kongono\ApplicationGraphContract\Vocabulary\NodeType;
use Kongono\ApplicationGraphContract\Vocabulary\RelationType;

/**
 * Carries bounded structural evidence from one validated Application Graph artifact.
 *
 * @internal The constructor is a package construction seam for exact admitted counts.
 */
#[Api(ApiStatus::Experimental)]
final readonly class ApplicationGraphArtifactInspection
{
    public function __construct(
        private string $artifactSha256,
        private GraphIdentity $graphIdentity,
        private ContractVersion $contractVersion,
        private int $nodeCount,
        private int $relationCount,
        private int $compositionInputCount,
        private int $compositionServiceCount,
        private int $compositionContractCount,
        private int $compositionRootCount,
        private int $compositionDependsOnCount,
        private int $compositionConsumesInputCount,
        private int $compositionImplementsContractCount,
        private int $compositionExposesRootCount,
    ) {
        $counts = [
            $nodeCount,
            $relationCount,
            $compositionInputCount,
            $compositionServiceCount,
            $compositionContractCount,
            $compositionRootCount,
            $compositionDependsOnCount,
            $compositionConsumesInputCount,
            $compositionImplementsContractCount,
            $compositionExposesRootCount,
        ];
        if (
            1 !== preg_match('/\A[a-f0-9]{64}\z/D', $artifactSha256)
            || array_any($counts, static fn(int $count): bool => $count < 0)
            || $nodeCount !== $compositionInputCount + $compositionServiceCount + $compositionContractCount + $compositionRootCount
            || $relationCount !== $compositionDependsOnCount + $compositionConsumesInputCount + $compositionImplementsContractCount + $compositionExposesRootCount
        ) {
            throw InvalidApplicationGraphArtifactException::artifact('invalid_inspection_counts');
        }
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
        return match ($type) {
            NodeType::CompositionInput => $this->compositionInputCount,
            NodeType::CompositionService => $this->compositionServiceCount,
            NodeType::CompositionContract => $this->compositionContractCount,
            NodeType::CompositionRoot => $this->compositionRootCount,
        };
    }

    public function relationTypeCount(RelationType $type): int
    {
        return match ($type) {
            RelationType::DependsOn => $this->compositionDependsOnCount,
            RelationType::ConsumesInput => $this->compositionConsumesInputCount,
            RelationType::ImplementsContract => $this->compositionImplementsContractCount,
            RelationType::ExposesRoot => $this->compositionExposesRootCount,
        };
    }
}
