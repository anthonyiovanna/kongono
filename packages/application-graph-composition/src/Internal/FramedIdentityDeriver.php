<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphComposition\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ApplicationGraphContract\Identity\NodeIdentity;
use Kongono\ApplicationGraphContract\Identity\ProducerIdentity;
use Kongono\ApplicationGraphContract\Identity\RelationIdentity;
use Kongono\ApplicationGraphContract\Identity\SourceIdentity;
use Kongono\ApplicationGraphContract\Vocabulary\NodeType;
use Kongono\ApplicationGraphContract\Vocabulary\RelationType;

/** Derives category-separated SHA-256 identities from unambiguous length-framed bytes. */
#[Api(ApiStatus::Internal)]
final class FramedIdentityDeriver
{
    public function producer(): ProducerIdentity
    {
        return new ProducerIdentity($this->digest('kongono.graph.composition.producer.v1', [
            'kongono/application-graph-composition',
            'kongono.composition/2',
            '1',
        ]));
    }

    public function source(string $artifactSha256): SourceIdentity
    {
        return new SourceIdentity($this->digest('kongono.graph.composition.source.v1', [$artifactSha256]));
    }

    /** @param non-empty-list<string> $semanticFields */
    public function node(NodeType $type, array $semanticFields): NodeIdentity
    {
        $domain = match ($type) {
            NodeType::CompositionInput => 'kongono.graph.composition.node.input.v1',
            NodeType::CompositionService => 'kongono.graph.composition.node.service.v1',
            NodeType::CompositionContract => 'kongono.graph.composition.node.contract.v1',
            NodeType::CompositionRoot => 'kongono.graph.composition.node.root.v1',
        };

        return new NodeIdentity($this->digest($domain, [$type->value, ...$semanticFields]));
    }

    public function relation(RelationType $type, NodeIdentity $source, NodeIdentity $target): RelationIdentity
    {
        return new RelationIdentity($this->digest(
            'kongono.graph.composition.relation.v1',
            [$type->value, $source->value(), $target->value()],
        ));
    }

    /** @param list<string> $fields */
    private function digest(string $domain, array $fields): string
    {
        $context = hash_init('sha256');
        hash_update($context, $domain);
        foreach ($fields as $field) {
            hash_update($context, pack('N', strlen($field)));
            hash_update($context, $field);
        }

        return hash_final($context);
    }
}
