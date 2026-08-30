<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphBuild\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ApplicationGraphBuild\Build\ApplicationGraphBuilder;
use Kongono\ApplicationGraphBuild\Exception\InvalidGraphBuildException;
use Kongono\ApplicationGraphBuild\Graph\AuthoritativeApplicationGraph;
use Kongono\ApplicationGraphBuild\Graph\GraphField;
use Kongono\ApplicationGraphBuild\Graph\GraphNode;
use Kongono\ApplicationGraphBuild\Graph\GraphRelation;
use Kongono\ApplicationGraphBuild\Ir\GraphFieldRecord;
use Kongono\ApplicationGraphBuild\Ir\GraphNodeRecord;
use Kongono\ApplicationGraphBuild\Ir\GraphRelationRecord;
use Kongono\ApplicationGraphContract\ContractVersion;
use Kongono\ApplicationGraphContract\Schema\FieldSchema;
use Kongono\ApplicationGraphContract\Schema\SchemaCatalog;
use Kongono\ApplicationGraphContract\Vocabulary\NodeType;

/** Validates one complete record set and creates fresh canonical fact objects. */
#[Api(ApiStatus::Internal)]
final class GraphConstructionValidator
{
    /**
     * @param array<array-key, mixed> $nodeRecords
     * @param array<array-key, mixed> $relationRecords
     */
    public function construct(array $nodeRecords, array $relationRecords): AuthoritativeApplicationGraph
    {
        if (!array_is_list($nodeRecords)) {
            throw InvalidGraphBuildException::input('node_records_not_list', 'node', count($nodeRecords));
        }
        if (!array_is_list($relationRecords)) {
            throw InvalidGraphBuildException::input('relation_records_not_list', 'relation', count($relationRecords));
        }
        if (count($nodeRecords) > ApplicationGraphBuilder::MAX_NODES) {
            throw InvalidGraphBuildException::limit('node_count', 'node', count($nodeRecords));
        }
        if (count($relationRecords) > ApplicationGraphBuilder::MAX_RELATIONS) {
            throw InvalidGraphBuildException::limit('relation_count', 'relation', count($relationRecords));
        }

        $catalog = SchemaCatalog::version1();
        $nodes = [];
        $nodeTypes = [];
        foreach ($nodeRecords as $position => $record) {
            if (!$record instanceof GraphNodeRecord) {
                throw InvalidGraphBuildException::input('invalid_node_record', 'node', count($nodeRecords), $position);
            }
            $identity = $record->identity()->value();
            if (isset($nodeTypes[$identity])) {
                throw InvalidGraphBuildException::duplicate('duplicate_node_identity', 'node');
            }
            $nodeTypes[$identity] = $record->type();
            $nodes[] = new GraphNode(
                $record->identity(),
                $record->type(),
                self::admitFields($record->fields(), $catalog->nodeSchema($record->type())->fields(), 'node'),
                $record->provenance(),
            );
        }

        $relations = [];
        $relationIdentities = [];
        $relationTuples = [];
        foreach ($relationRecords as $position => $record) {
            if (!$record instanceof GraphRelationRecord) {
                throw InvalidGraphBuildException::input('invalid_relation_record', 'relation', count($relationRecords), $position);
            }
            $identity = $record->identity()->value();
            if (isset($relationIdentities[$identity])) {
                throw InvalidGraphBuildException::duplicate('duplicate_relation_identity', 'relation');
            }
            $relationIdentities[$identity] = true;
            $tuple = $record->type()->value . "\0" . $record->source()->value() . "\0" . $record->target()->value();
            if (isset($relationTuples[$tuple])) {
                throw InvalidGraphBuildException::duplicate('duplicate_relation_tuple', 'relation');
            }
            $relationTuples[$tuple] = true;

            $sourceType = $nodeTypes[$record->source()->value()] ?? null;
            $targetType = $nodeTypes[$record->target()->value()] ?? null;
            if (!$sourceType instanceof NodeType) {
                throw InvalidGraphBuildException::endpoint('missing_source');
            }
            if (!$targetType instanceof NodeType) {
                throw InvalidGraphBuildException::endpoint('missing_target');
            }
            $schema = $catalog->relationSchema($record->type());
            if (!in_array($sourceType, $schema->allowedSources(), true)) {
                throw InvalidGraphBuildException::endpoint('source_type');
            }
            if (!in_array($targetType, $schema->allowedTargets(), true)) {
                throw InvalidGraphBuildException::endpoint('target_type');
            }
            $relations[] = new GraphRelation(
                $record->identity(),
                $record->type(),
                $record->source(),
                $record->target(),
                self::admitFields($record->fields(), $schema->fields(), 'relation'),
                $record->provenance(),
            );
        }

        usort($nodes, static fn(GraphNode $left, GraphNode $right): int => strcmp($left->identity()->value(), $right->identity()->value()));
        usort($relations, static fn(GraphRelation $left, GraphRelation $right): int => strcmp($left->identity()->value(), $right->identity()->value()));

        return new AuthoritativeApplicationGraph(ContractVersion::Version1, $nodes, $relations);
    }

    /**
     * @param list<GraphFieldRecord> $records
     * @param list<FieldSchema>      $schemas
     * @return list<GraphField>
     */
    private static function admitFields(array $records, array $schemas, string $recordKind): array
    {
        $recordByName = [];
        foreach ($records as $record) {
            $recordByName[$record->name()->value()] = $record;
        }
        $schemaByName = [];
        foreach ($schemas as $schema) {
            $schemaByName[$schema->name()->value()] = $schema;
        }
        foreach ($records as $position => $record) {
            if (!isset($schemaByName[$record->name()->value()])) {
                throw InvalidGraphBuildException::fieldSet('unknown_field', $recordKind, count($records), $position);
            }
        }

        $fields = [];
        foreach ($schemas as $position => $schema) {
            $record = $recordByName[$schema->name()->value()] ?? null;
            if (!$record instanceof GraphFieldRecord) {
                if ($schema->isRequired()) {
                    throw InvalidGraphBuildException::fieldSet('missing_required_field', $recordKind, count($records), $position);
                }
                continue;
            }
            $fields[] = new GraphField(
                $schema->name(),
                $schema->type(),
                $record->value(),
                $schema->isRequired(),
                $schema->disclosure(),
                $schema->visibility(),
            );
        }

        return $fields;
    }
}
