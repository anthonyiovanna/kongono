<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphArtifact\Artifact;

use JsonException;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ApplicationGraphArtifact\Exception\InvalidApplicationGraphArtifactException;
use Kongono\ApplicationGraphArtifact\Exception\StaleApplicationGraphArtifactException;
use Kongono\ApplicationGraphArtifact\Identity\GraphIdentity;
use Kongono\ApplicationGraphArtifact\Inspection\ApplicationGraphArtifactInspection;
use Kongono\ApplicationGraphArtifact\Internal\CanonicalJson;
use Kongono\ApplicationGraphArtifact\Internal\GraphArtifactCodec;
use Kongono\ApplicationGraphBuild\Build\ApplicationGraphBuilder;
use Kongono\ApplicationGraphBuild\Graph\AuthoritativeApplicationGraph;
use Kongono\ApplicationGraphBuild\Ir\GraphFieldRecord;
use Kongono\ApplicationGraphBuild\Ir\GraphNodeRecord;
use Kongono\ApplicationGraphBuild\Ir\GraphRelationRecord;
use Kongono\ApplicationGraphContract\ContractVersion;
use Kongono\ApplicationGraphContract\Identity\NodeIdentity;
use Kongono\ApplicationGraphContract\Identity\ProducerIdentity;
use Kongono\ApplicationGraphContract\Identity\RelationIdentity;
use Kongono\ApplicationGraphContract\Identity\SourceIdentity;
use Kongono\ApplicationGraphContract\Provenance\DerivationVersion;
use Kongono\ApplicationGraphContract\Provenance\GraphProvenance;
use Kongono\ApplicationGraphContract\Provenance\ProducerContract;
use Kongono\ApplicationGraphContract\Schema\FieldName;
use Kongono\ApplicationGraphContract\Vocabulary\NodeType;
use Kongono\ApplicationGraphContract\Vocabulary\RelationType;
use Throwable;

/** Proves strict structure and equality with one caller-supplied current Graph. */
#[Api(ApiStatus::Experimental)]
final class ApplicationGraphArtifactValidator
{
    public function validateStructure(string $artifactBytes): ApplicationGraphArtifact
    {
        $this->bytes($artifactBytes);
        try {
            $data = CanonicalJson::decodeObject($artifactBytes, 64);
        } catch (JsonException $failure) {
            throw InvalidApplicationGraphArtifactException::artifact('invalid_json', 'artifact', [], $failure);
        }
        if (CanonicalJson::encode($data) !== $artifactBytes) {
            $this->invalid('noncanonical_json');
        }
        $this->keys($data, ['schema','version','graph_identity','graph_sha256','builder','graph']);
        if ('kongono.application-graph-artifact' !== ($data['schema'] ?? null) || 1 !== ($data['version'] ?? null)) {
            $this->invalid('schema_or_version');
        }
        $builder = $this->object($data['builder'] ?? null, 'builder');
        $this->keys($builder, ['package','contract_version']);
        if ('kongono/application-graph-artifact' !== ($builder['package'] ?? null) || 1 !== ($builder['contract_version'] ?? null)) {
            $this->invalid('builder_contract', 'builder');
        }
        $graph = $this->object($data['graph'] ?? null, 'graph');
        $this->keys($graph, ['contract_version','node_count','relation_count','nodes','relations']);
        if (1 !== ($graph['contract_version'] ?? null)) {
            $this->invalid('graph_contract_version', 'graph');
        }
        $nodes = $this->list($graph['nodes'] ?? null, 'node');
        $relations = $this->list($graph['relations'] ?? null, 'relation');
        $nodeCount = $this->count($graph['node_count'] ?? null, count($nodes), ApplicationGraphBuilder::MAX_NODES, 'node');
        $relationCount = $this->count($graph['relation_count'] ?? null, count($relations), ApplicationGraphBuilder::MAX_RELATIONS, 'relation');
        try {
            $fresh = (new ApplicationGraphBuilder())->build($this->nodes($nodes), $this->relations($relations));
        } catch (Throwable $failure) {
            if ($failure instanceof \Error) {
                throw $failure;
            }
            throw InvalidApplicationGraphArtifactException::artifact('graph_readmission_failed', 'graph', [], $failure);
        }
        if (CanonicalJson::encode(GraphArtifactCodec::graphData($fresh)) !== CanonicalJson::encode($graph)) {
            $this->invalid('graph_canonical_mismatch', 'graph');
        }
        $graphSha = $this->digest($data['graph_sha256'] ?? null);
        if (!hash_equals($graphSha, hash('sha256', CanonicalJson::encode($graph)))) {
            $this->invalid('graph_digest_mismatch', 'graph');
        }
        $identity = new GraphIdentity($graphSha);
        if ($identity->value() !== ($data['graph_identity'] ?? null)) {
            $this->invalid('graph_identity_mismatch', 'graph');
        }

        $artifactSha256 = hash('sha256', $artifactBytes);

        return new ApplicationGraphArtifact(
            $artifactBytes,
            $artifactSha256,
            $identity,
            $graphSha,
            $nodeCount,
            $relationCount,
            $this->inspection($artifactSha256, $identity, $fresh),
        );
    }

    public function validateCurrent(AuthoritativeApplicationGraph $currentGraph, string $artifactBytes): ApplicationGraphArtifact
    {
        $artifact = $this->validateStructure($artifactBytes);
        if (!hash_equals((new ApplicationGraphArtifactBuilder())->build($currentGraph)->canonicalBytes(), $artifactBytes)) {
            throw StaleApplicationGraphArtifactException::graphStateMismatch();
        }
        return $artifact;
    }

    /**
     * @param list<mixed> $values
     * @return list<GraphNodeRecord>
     */
    private function nodes(array $values): array
    {
        $records = [];
        foreach ($values as $position => $value) {
            $node = $this->object($value, 'node');
            $this->keys($node, ['identity','type','fields','provenance']);
            $records[] = new GraphNodeRecord(ContractVersion::Version1, new NodeIdentity($this->prefixedDigest($node['identity'] ?? null, 'kgn:graph:v1:node:')), NodeType::tryFrom($this->text($node['type'] ?? null)) ?? $this->never('node_type', 'node'), $this->fields($this->list($node['fields'] ?? null, 'field')), $this->provenance($node['provenance'] ?? null));
        }return $records;
    }

    /**
     * @param list<mixed> $values
     * @return list<GraphRelationRecord>
     */
    private function relations(array $values): array
    {
        $records = [];
        foreach ($values as $value) {
            $relation = $this->object($value, 'relation');
            $this->keys($relation, ['identity','type','source','target','fields','provenance']);
            $records[] = new GraphRelationRecord(ContractVersion::Version1, new RelationIdentity($this->prefixedDigest($relation['identity'] ?? null, 'kgn:graph:v1:relation:')), RelationType::tryFrom($this->text($relation['type'] ?? null)) ?? $this->never('relation_type', 'relation'), new NodeIdentity($this->prefixedDigest($relation['source'] ?? null, 'kgn:graph:v1:node:')), new NodeIdentity($this->prefixedDigest($relation['target'] ?? null, 'kgn:graph:v1:node:')), $this->fields($this->list($relation['fields'] ?? null, 'field')), $this->provenance($relation['provenance'] ?? null));
        }return $records;
    }

    /**
     * @param list<mixed> $values
     * @return list<GraphFieldRecord>
     */
    private function fields(array $values): array
    {
        if (count($values) > 32) {
            throw InvalidApplicationGraphArtifactException::limit('field_count', 'field', count($values));
        }$records = [];
        foreach ($values as $value) {
            $field = $this->object($value, 'field');
            $this->keys($field, ['name','type','value','required','disclosure','visibility']);
            if ('text' !== ($field['type'] ?? null) || !is_bool($field['required'] ?? null) || !in_array($field['disclosure'] ?? null, ['safe','protected'], true) || !in_array($field['visibility'] ?? null, ['public','application','framework_internal'], true)) {
                $this->invalid('field_metadata', 'field');
            }$records[] = new GraphFieldRecord(new FieldName($this->text($field['name'] ?? null)), $this->text($field['value'] ?? null));
        }return $records;
    }

    private function provenance(mixed $value): GraphProvenance
    {
        $p = $this->object($value, 'provenance');
        $this->keys($p, ['producer_identity','source_identity','producer_contract','derivation_version','source_sha256']);
        if ('kongono.composition/2' !== ($p['producer_contract'] ?? null) || 1 !== ($p['derivation_version'] ?? null)) {
            $this->invalid('provenance_contract', 'provenance');
        }return new GraphProvenance(new ProducerIdentity($this->prefixedDigest($p['producer_identity'] ?? null, 'kgn:graph:v1:producer:')), new SourceIdentity($this->prefixedDigest($p['source_identity'] ?? null, 'kgn:graph:v1:source:')), ProducerContract::CompositionV2, DerivationVersion::Version1, $this->digest($p['source_sha256'] ?? null));
    }

    private function bytes(string $bytes): void
    {
        if ('' === $bytes || strlen($bytes) > ApplicationGraphArtifactBuilder::MAX_ARTIFACT_BYTES || str_starts_with($bytes, "\xEF\xBB\xBF") || str_contains($bytes, "\r") || str_contains($bytes, "\0") || 1 !== preg_match('//u', $bytes) || !str_ends_with($bytes, "\n") || str_ends_with($bytes, "\n\n")) {
            if (strlen($bytes) > ApplicationGraphArtifactBuilder::MAX_ARTIFACT_BYTES) {
                throw InvalidApplicationGraphArtifactException::limit('artifact_bytes', 'artifact', strlen($bytes));
            }$this->invalid('artifact_bytes');
        }
    }
    /**
     * @param array<string,mixed> $value
     * @param list<string> $keys
     */
    private function keys(array $value, array $keys): void
    {
        if (array_keys($value) !== $keys) {
            $this->invalid('invalid_shape');
        }
    }
    /** @return array<string,mixed> */
    private function object(mixed $value, string $kind): array
    {
        if (!is_array($value) || array_is_list($value)) {
            $this->invalid('invalid_shape', $kind);
        }
        $object = [];
        foreach ($value as $key => $item) {
            if (!is_string($key)) {
                $this->invalid('invalid_shape', $kind);
            }$object[$key] = $item;
        }
        return $object;
    }
    /** @return list<mixed> */ private function list(mixed $value, string $kind): array
    {
        if (!is_array($value) || !array_is_list($value)) {
            $this->invalid('invalid_shape', $kind);
        }return $value;
    }
    private function count(mixed $value, int $actual, int $max, string $kind): int
    {
        if (!is_int($value) || $value !== $actual) {
            $this->invalid('count_mismatch', $kind);
        }if ($value > $max) {
            throw InvalidApplicationGraphArtifactException::limit($kind . '_count', $kind, $value);
        }return $value;
    }
    private function digest(mixed $value): string
    {
        if (!is_string($value) || 1 !== preg_match('/\A[a-f0-9]{64}\z/D', $value)) {
            $this->invalid('digest_grammar');
        }return $value;
    }
    private function prefixedDigest(mixed $value, string $prefix): string
    {
        if (!is_string($value) || !str_starts_with($value, $prefix)) {
            $this->invalid('identity_grammar');
        }return $this->digest(substr($value, strlen($prefix)));
    }
    private function text(mixed $value): string
    {
        if (!is_string($value)) {
            $this->invalid('invalid_scalar');
        }return $value;
    }
    private function never(string $violation, string $kind): never
    {
        $this->invalid($violation, $kind);
    }
    private function invalid(string $violation, string $kind = 'artifact'): never
    {
        throw InvalidApplicationGraphArtifactException::artifact($violation, $kind);
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
