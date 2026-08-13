<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraph\Model;

use Kongono\ApplicationGraph\Diagnostic\GraphDiagnostic;
use Kongono\ApplicationGraph\Exception\InvalidGraphException;
use Kongono\Kernel\Api\Api;
use Kongono\Kernel\Api\ApiStatus;

/**
 * Owns one immutable, structurally validated Application Graph snapshot.
 *
 * Construction rejects malformed lists, duplicate node keys, dangling relation
 * endpoints, and duplicate relation triples before publishing state. Returned
 * nodes and relations use binary string order with no locale or Unicode
 * normalization. Exact kind selection scans the canonical node or relation list
 * in O(N) or O(R) time, while outgoing and incoming selection scans the canonical
 * relation list in O(R) time. No selector retains an index or cache. The instance
 * performs no I/O or ambient-state access and is not yet Kongono's complete
 * authoritative application representation.
 *
 * Stability: Experimental.
 */
#[Api(ApiStatus::Experimental)]
final readonly class ApplicationGraph
{
    /** @var list<Node> */
    private array $nodes;

    /** @var list<Relation> */
    private array $relations;

    /**
     * @param list<Node>     $nodes
     * @param list<Relation> $relations
     */
    public function __construct(array $nodes, array $relations)
    {
        self::assertRuntimeInputs($nodes, $relations);

        usort($nodes, static fn(Node $left, Node $right): int => strcmp(
            $left->key()->value(),
            $right->key()->value(),
        ));

        for ($index = 1, $count = count($nodes); $index < $count; ++$index) {
            if ($nodes[$index - 1]->key()->value() === $nodes[$index]->key()->value()) {
                throw new InvalidGraphException(
                    GraphDiagnostic::DuplicateNodeKey,
                    sprintf(
                        'Duplicate node key "%s" is not allowed.',
                        $nodes[$index]->key()->value(),
                    ),
                );
            }
        }

        foreach ($relations as $relation) {
            if (null === self::findNode($nodes, $relation->source())) {
                throw new InvalidGraphException(
                    GraphDiagnostic::MissingRelationSource,
                    sprintf(
                        'Relation source key "%s" does not reference an existing node.',
                        $relation->source()->value(),
                    ),
                );
            }

            if (null === self::findNode($nodes, $relation->target())) {
                throw new InvalidGraphException(
                    GraphDiagnostic::MissingRelationTarget,
                    sprintf(
                        'Relation target key "%s" does not reference an existing node.',
                        $relation->target()->value(),
                    ),
                );
            }
        }

        usort($relations, static function (Relation $left, Relation $right): int {
            $sourceOrder = strcmp($left->source()->value(), $right->source()->value());

            if (0 !== $sourceOrder) {
                return $sourceOrder;
            }

            $kindOrder = strcmp($left->kind()->value(), $right->kind()->value());

            return 0 !== $kindOrder
                ? $kindOrder
                : strcmp($left->target()->value(), $right->target()->value());
        });

        for ($index = 1, $count = count($relations); $index < $count; ++$index) {
            if (self::sameRelation($relations[$index - 1], $relations[$index])) {
                $duplicate = $relations[$index];

                throw new InvalidGraphException(
                    GraphDiagnostic::DuplicateRelationTriple,
                    sprintf(
                        'Duplicate relation triple "%s" -> "%s" -> "%s" is not allowed.',
                        $duplicate->source()->value(),
                        $duplicate->kind()->value(),
                        $duplicate->target()->value(),
                    ),
                );
            }
        }

        $this->nodes = $nodes;
        $this->relations = $relations;
    }

    /** @return list<Node> */
    public function nodes(): array
    {
        return $this->nodes;
    }

    /** @return list<Node> */
    public function nodesOfKind(NodeKind $kind): array
    {
        $matches = [];

        foreach ($this->nodes as $node) {
            if ($node->kind()->value() === $kind->value()) {
                $matches[] = $node;
            }
        }

        return $matches;
    }

    /** @return list<Relation> */
    public function relations(): array
    {
        return $this->relations;
    }

    /** @return list<Relation> */
    public function relationsOfKind(RelationKind $kind): array
    {
        $matches = [];

        foreach ($this->relations as $relation) {
            if ($relation->kind()->value() === $kind->value()) {
                $matches[] = $relation;
            }
        }

        return $matches;
    }

    /** @return list<Relation> */
    public function outgoingRelations(NodeKey $source): array
    {
        $matches = [];

        foreach ($this->relations as $relation) {
            if ($relation->source()->value() === $source->value()) {
                $matches[] = $relation;
            }
        }

        return $matches;
    }

    /** @return list<Relation> */
    public function incomingRelations(NodeKey $target): array
    {
        $matches = [];

        foreach ($this->relations as $relation) {
            if ($relation->target()->value() === $target->value()) {
                $matches[] = $relation;
            }
        }

        return $matches;
    }

    public function hasNode(NodeKey $key): bool
    {
        return null !== $this->node($key);
    }

    public function node(NodeKey $key): ?Node
    {
        return self::findNode($this->nodes, $key);
    }

    /**
     * @param array<array-key, mixed> $nodes
     * @param array<array-key, mixed> $relations
     */
    private static function assertRuntimeInputs(array $nodes, array $relations): void
    {
        if (!array_is_list($nodes)) {
            throw new InvalidGraphException(
                GraphDiagnostic::NodesNotList,
                'Nodes must be supplied as a zero-based consecutive list.',
            );
        }

        if (!array_is_list($relations)) {
            throw new InvalidGraphException(
                GraphDiagnostic::RelationsNotList,
                'Relations must be supplied as a zero-based consecutive list.',
            );
        }

        foreach ($nodes as $index => $node) {
            if (!$node instanceof Node) {
                throw new InvalidGraphException(
                    GraphDiagnostic::InvalidNodeEntry,
                    sprintf('Nodes list entry at index %s must be a Node.', $index),
                );
            }
        }

        foreach ($relations as $index => $relation) {
            if (!$relation instanceof Relation) {
                throw new InvalidGraphException(
                    GraphDiagnostic::InvalidRelationEntry,
                    sprintf(
                        'Relations list entry at index %s must be a Relation.',
                        $index,
                    ),
                );
            }
        }
    }

    /** @param list<Node> $nodes */
    private static function findNode(array $nodes, NodeKey $key): ?Node
    {
        foreach ($nodes as $node) {
            if ($node->key()->value() === $key->value()) {
                return $node;
            }
        }

        return null;
    }

    private static function sameRelation(Relation $left, Relation $right): bool
    {
        return $left->source()->value() === $right->source()->value()
            && $left->kind()->value() === $right->kind()->value()
            && $left->target()->value() === $right->target()->value();
    }
}
