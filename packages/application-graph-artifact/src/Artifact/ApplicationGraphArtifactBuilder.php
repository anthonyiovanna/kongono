<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphArtifact\Artifact;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ApplicationGraphArtifact\Exception\InvalidApplicationGraphArtifactException;
use Kongono\ApplicationGraphArtifact\Identity\GraphIdentity;
use Kongono\ApplicationGraphArtifact\Inspection\ApplicationGraphArtifactInspection;
use Kongono\ApplicationGraphArtifact\Internal\CanonicalJson;
use Kongono\ApplicationGraphArtifact\Internal\GraphArtifactCodec;
use Kongono\ApplicationGraphBuild\Build\ApplicationGraphBuilder;
use Kongono\ApplicationGraphBuild\Exception\ExceptionInterface as GraphBuildException;
use Kongono\ApplicationGraphBuild\Graph\AuthoritativeApplicationGraph;
use Kongono\ApplicationGraphContract\Exception\ExceptionInterface as GraphContractException;
use Kongono\ApplicationGraphContract\Vocabulary\NodeType;
use Kongono\ApplicationGraphContract\Vocabulary\RelationType;

/** Re-admits one supplied Graph and serializes its fresh canonical facts. */
#[Api(ApiStatus::Experimental)]
final class ApplicationGraphArtifactBuilder
{
    public const int MAX_ARTIFACT_BYTES = 16_777_216;
    public const int CONTRACT_VERSION = 1;

    public function build(AuthoritativeApplicationGraph $graph): ApplicationGraphArtifact
    {
        try {
            $fresh = (new ApplicationGraphBuilder())->build(GraphArtifactCodec::nodeRecords($graph), GraphArtifactCodec::relationRecords($graph));
        } catch (GraphBuildException|GraphContractException $failure) {
            throw InvalidApplicationGraphArtifactException::graph('graph_readmission_failed', 'graph', [], $failure);
        }

        $graphData = GraphArtifactCodec::graphData($fresh);
        $graphBytes = CanonicalJson::encode($graphData);
        $graphSha256 = hash('sha256', $graphBytes);
        $identity = new GraphIdentity($graphSha256);
        $artifactBytes = CanonicalJson::encode([
            'schema' => 'kongono.application-graph-artifact',
            'version' => 1,
            'graph_identity' => $identity->value(),
            'graph_sha256' => $graphSha256,
            'builder' => ['package' => 'kongono/application-graph-artifact', 'contract_version' => self::CONTRACT_VERSION],
            'graph' => $graphData,
        ]);
        if (strlen($artifactBytes) > self::MAX_ARTIFACT_BYTES) {
            throw InvalidApplicationGraphArtifactException::limit('artifact_bytes', 'artifact', strlen($artifactBytes));
        }

        $artifactSha256 = hash('sha256', $artifactBytes);

        return new ApplicationGraphArtifact(
            $artifactBytes,
            $artifactSha256,
            $identity,
            $graphSha256,
            count($fresh->nodes()),
            count($fresh->relations()),
            $this->inspection($artifactSha256, $identity, $fresh),
        );
    }

    private function inspection(string $artifactSha256, GraphIdentity $identity, AuthoritativeApplicationGraph $graph): ApplicationGraphArtifactInspection
    {
        $nodeCounts = [0, 0, 0, 0];
        foreach ($graph->nodes() as $node) {
            ++$nodeCounts[match ($node->type()) {
                NodeType::CompositionInput => 0,
                NodeType::CompositionService => 1,
                NodeType::CompositionContract => 2,
                NodeType::CompositionRoot => 3,
            }];
        }
        $relationCounts = [0, 0, 0, 0];
        foreach ($graph->relations() as $relation) {
            ++$relationCounts[match ($relation->type()) {
                RelationType::DependsOn => 0,
                RelationType::ConsumesInput => 1,
                RelationType::ImplementsContract => 2,
                RelationType::ExposesRoot => 3,
            }];
        }

        return new ApplicationGraphArtifactInspection(
            $artifactSha256,
            $identity,
            $graph->version(),
            count($graph->nodes()),
            count($graph->relations()),
            ...$nodeCounts,
            ...$relationCounts,
        );
    }
}
