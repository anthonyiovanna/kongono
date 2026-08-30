<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphArtifact\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ApplicationGraphBuild\Graph\AuthoritativeApplicationGraph;
use Kongono\ApplicationGraphBuild\Graph\GraphField;
use Kongono\ApplicationGraphBuild\Graph\GraphNode;
use Kongono\ApplicationGraphBuild\Graph\GraphRelation;
use Kongono\ApplicationGraphBuild\Ir\GraphFieldRecord;
use Kongono\ApplicationGraphBuild\Ir\GraphNodeRecord;
use Kongono\ApplicationGraphBuild\Ir\GraphRelationRecord;

/** Converts admitted Graph facts to exact canonical data and fresh IR records. */
#[Api(ApiStatus::Internal)]
final class GraphArtifactCodec
{
    /** @return array{contract_version:int,node_count:int,relation_count:int,nodes:list<array<string,mixed>>,relations:list<array<string,mixed>>} */
    public static function graphData(AuthoritativeApplicationGraph $graph): array
    {
        $nodes = array_map(self::nodeData(...), $graph->nodes());
        $relations = array_map(self::relationData(...), $graph->relations());

        return ['contract_version' => $graph->version()->value, 'node_count' => count($nodes), 'relation_count' => count($relations), 'nodes' => $nodes, 'relations' => $relations];
    }

    /** @return list<GraphNodeRecord> */
    public static function nodeRecords(AuthoritativeApplicationGraph $graph): array
    {
        return array_map(static fn(GraphNode $node): GraphNodeRecord => new GraphNodeRecord(
            $graph->version(),
            $node->identity(),
            $node->type(),
            array_map(static fn(GraphField $field): GraphFieldRecord => new GraphFieldRecord($field->name(), $field->value()), $node->fields()),
            $node->provenance(),
        ), $graph->nodes());
    }

    /** @return list<GraphRelationRecord> */
    public static function relationRecords(AuthoritativeApplicationGraph $graph): array
    {
        return array_map(static fn(GraphRelation $relation): GraphRelationRecord => new GraphRelationRecord(
            $graph->version(),
            $relation->identity(),
            $relation->type(),
            $relation->source(),
            $relation->target(),
            array_map(static fn(GraphField $field): GraphFieldRecord => new GraphFieldRecord($field->name(), $field->value()), $relation->fields()),
            $relation->provenance(),
        ), $graph->relations());
    }

    /** @return array<string,mixed> */
    private static function nodeData(GraphNode $node): array
    {
        return ['identity' => $node->identity()->value(), 'type' => $node->type()->value, 'fields' => array_map(self::fieldData(...), $node->fields()), 'provenance' => self::provenanceData($node->provenance())];
    }

    /** @return array<string,mixed> */
    private static function relationData(GraphRelation $relation): array
    {
        return ['identity' => $relation->identity()->value(), 'type' => $relation->type()->value, 'source' => $relation->source()->value(), 'target' => $relation->target()->value(), 'fields' => array_map(self::fieldData(...), $relation->fields()), 'provenance' => self::provenanceData($relation->provenance())];
    }

    /** @return array{name:string,type:string,value:string,required:bool,disclosure:string,visibility:string} */
    private static function fieldData(GraphField $field): array
    {
        return ['name' => $field->name()->value(), 'type' => $field->type()->value, 'value' => $field->value(), 'required' => $field->isRequired(), 'disclosure' => $field->disclosure()->value, 'visibility' => $field->visibility()->value];
    }

    /** @return array{producer_identity:string,source_identity:string,producer_contract:string,derivation_version:int,source_sha256:string} */
    private static function provenanceData(\Kongono\ApplicationGraphContract\Provenance\GraphProvenance $provenance): array
    {
        return ['producer_identity' => $provenance->producerIdentity()->value(), 'source_identity' => $provenance->sourceIdentity()->value(), 'producer_contract' => $provenance->producerContract()->value, 'derivation_version' => $provenance->derivationVersion()->value, 'source_sha256' => $provenance->sourceSha256()];
    }
}
