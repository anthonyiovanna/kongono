<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraph\Tests\Unit;

use Kongono\ApplicationGraph\Exception\InvalidGraphException;
use Kongono\ApplicationGraph\Model\ApplicationGraph;
use Kongono\ApplicationGraph\Model\Node;
use Kongono\ApplicationGraph\Model\NodeKey;
use Kongono\ApplicationGraph\Model\NodeKind;
use Kongono\ApplicationGraph\Model\Relation;
use Kongono\ApplicationGraph\Model\RelationKind;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class ApplicationGraphTest extends TestCase
{
    public function testEmptyGraphIsValid(): void
    {
        $graph = new ApplicationGraph([], []);

        self::assertSame([], $graph->nodes());
        self::assertSame([], $graph->relations());
    }

    public function testNodesUseCanonicalBinaryKeyOrder(): void
    {
        $graph = new ApplicationGraph([
            $this->node('a'),
            $this->node("a\0"),
            $this->node('Z'),
        ], []);

        self::assertSame(['Z', 'a', "a\0"], $this->nodeKeys($graph));
    }

    public function testRelationsUseCanonicalSourceKindTargetOrder(): void
    {
        $nodes = [$this->node('a'), $this->node('b'), $this->node('c')];
        $graph = new ApplicationGraph($nodes, [
            $this->relation('b', 'z', 'a'),
            $this->relation('a', 'z', 'c'),
            $this->relation('a', 'a', 'c'),
            $this->relation('a', 'a', 'b'),
        ]);

        self::assertSame([
            ['a', 'a', 'b'],
            ['a', 'a', 'c'],
            ['a', 'z', 'c'],
            ['b', 'z', 'a'],
        ], $this->relationTriples($graph));
    }

    public function testLookupUsesEquivalentKeyValueRatherThanObjectIdentity(): void
    {
        $node = $this->node('same-value');
        $graph = new ApplicationGraph([$node], []);

        self::assertTrue($graph->hasNode(new NodeKey('same-value')));
        self::assertSame($node, $graph->node(new NodeKey('same-value')));
    }

    public function testMissingLookupReturnsNullAndFalse(): void
    {
        $graph = new ApplicationGraph([$this->node('present')], []);

        self::assertFalse($graph->hasNode(new NodeKey('missing')));
        self::assertNull($graph->node(new NodeKey('missing')));
    }

    public function testDuplicateNodeValuesAreRejectedAcrossDistinctObjects(): void
    {
        $this->expectException(InvalidGraphException::class);
        $this->expectExceptionMessageMatches('/duplicate node key/i');

        new ApplicationGraph([$this->node('duplicate'), $this->node('duplicate')], []);
    }

    public function testMissingRelationSourceIsRejected(): void
    {
        $this->expectException(InvalidGraphException::class);
        $this->expectExceptionMessageMatches('/source key.*missing-source/i');

        new ApplicationGraph([$this->node('target')], [$this->relation('missing-source', 'kind', 'target')]);
    }

    public function testMissingRelationTargetIsRejected(): void
    {
        $this->expectException(InvalidGraphException::class);
        $this->expectExceptionMessageMatches('/target key.*missing-target/i');

        new ApplicationGraph([$this->node('source')], [$this->relation('source', 'kind', 'missing-target')]);
    }

    public function testExactDuplicateRelationTripleIsRejected(): void
    {
        $nodes = [$this->node('source'), $this->node('target')];

        $this->expectException(InvalidGraphException::class);
        $this->expectExceptionMessageMatches('/duplicate relation triple/i');

        new ApplicationGraph($nodes, [
            $this->relation('source', 'kind', 'target'),
            $this->relation('source', 'kind', 'target'),
        ]);
    }

    public function testDifferentKindsBetweenSameEndpointsAreAccepted(): void
    {
        $graph = new ApplicationGraph(
            [$this->node('source'), $this->node('target')],
            [$this->relation('source', 'b', 'target'), $this->relation('source', 'a', 'target')],
        );

        self::assertSame([
            ['source', 'a', 'target'],
            ['source', 'b', 'target'],
        ], $this->relationTriples($graph));
    }

    public function testSelfRelationIsAccepted(): void
    {
        $graph = new ApplicationGraph([$this->node('self')], [$this->relation('self', 'kind', 'self')]);

        self::assertSame([['self', 'kind', 'self']], $this->relationTriples($graph));
    }

    public function testCycleIsAccepted(): void
    {
        $graph = new ApplicationGraph(
            [$this->node('a'), $this->node('b')],
            [$this->relation('b', 'kind', 'a'), $this->relation('a', 'kind', 'b')],
        );

        self::assertSame([
            ['a', 'kind', 'b'],
            ['b', 'kind', 'a'],
        ], $this->relationTriples($graph));
    }

    public function testArbitraryDelimiterAndControlValuesDoNotCollide(): void
    {
        $graph = new ApplicationGraph(
            [$this->node("a\0b"), $this->node('a'), $this->node("c\0d"), $this->node('d')],
            [
                $this->relation("a\0b", 'c', 'd'),
                $this->relation('a', "b\0c", 'd'),
                $this->relation('a', 'b', "c\0d"),
            ],
        );

        self::assertCount(3, $graph->relations());
        self::assertSame([
            ['a', 'b', "c\0d"],
            ['a', "b\0c", 'd'],
            ["a\0b", 'c', 'd'],
        ], $this->relationTriples($graph));
    }

    public function testMutatingCallerArraysDoesNotAlterSnapshot(): void
    {
        $nodes = [$this->node('a'), $this->node('b')];
        $relations = [$this->relation('a', 'kind', 'b')];
        $graph = new ApplicationGraph($nodes, $relations);

        $nodes[] = $this->node('c');
        $relations = [];

        self::assertSame(['a', 'b'], $this->nodeKeys($graph));
        self::assertCount(1, $graph->relations());
        self::assertSame([], $relations);
    }

    public function testMutatingReturnedArraysDoesNotAlterSnapshot(): void
    {
        $graph = new ApplicationGraph(
            [$this->node('a'), $this->node('b')],
            [$this->relation('a', 'kind', 'b')],
        );
        $nodes = $graph->nodes();
        $relations = $graph->relations();

        array_pop($nodes);
        array_pop($relations);

        self::assertSame(['a', 'b'], $this->nodeKeys($graph));
        self::assertCount(1, $graph->relations());
    }

    public function testAssociativeNodesArrayIsRejectedWithoutReindexing(): void
    {
        $this->expectException(InvalidGraphException::class);
        $this->expectExceptionMessageMatches('/nodes.*zero-based consecutive list/i');

        (new ReflectionClass(ApplicationGraph::class))->newInstance(['key' => $this->node('a')], []);
    }

    public function testGappedRelationsArrayIsRejectedWithoutReindexing(): void
    {
        $this->expectException(InvalidGraphException::class);
        $this->expectExceptionMessageMatches('/relations.*zero-based consecutive list/i');

        (new ReflectionClass(ApplicationGraph::class))->newInstance(
            [$this->node('a')],
            [1 => $this->relation('a', 'kind', 'a')],
        );
    }

    public function testNonNodeListEntryIsRejectedAtRuntime(): void
    {
        $this->expectException(InvalidGraphException::class);
        $this->expectExceptionMessageMatches('/nodes list entry.*must be a node/i');

        (new ReflectionClass(ApplicationGraph::class))->newInstance(['not-a-node'], []);
    }

    public function testNonRelationListEntryIsRejectedAtRuntime(): void
    {
        $this->expectException(InvalidGraphException::class);
        $this->expectExceptionMessageMatches('/relations list entry.*must be a relation/i');

        (new ReflectionClass(ApplicationGraph::class))->newInstance([$this->node('a')], ['not-a-relation']);
    }

    public function testConstructionIsDeterministicAndInstanceOwnedWithoutExternalInputs(): void
    {
        $nodes = [$this->node('b'), $this->node('a')];
        $relations = [$this->relation('b', 'kind', 'a')];

        $first = new ApplicationGraph($nodes, $relations);
        $second = new ApplicationGraph($nodes, $relations);

        self::assertNotSame($first, $second);
        self::assertSame($this->nodeKeys($first), $this->nodeKeys($second));
        self::assertSame($this->relationTriples($first), $this->relationTriples($second));
    }

    private function node(string $key, string $kind = 'kind'): Node
    {
        return new Node(new NodeKey($key), new NodeKind($kind));
    }

    private function relation(string $source, string $kind, string $target): Relation
    {
        return new Relation(new NodeKey($source), new RelationKind($kind), new NodeKey($target));
    }

    /** @return list<string> */
    private function nodeKeys(ApplicationGraph $graph): array
    {
        return array_map(static fn(Node $node): string => $node->key()->value(), $graph->nodes());
    }

    /** @return list<array{string, string, string}> */
    private function relationTriples(ApplicationGraph $graph): array
    {
        return array_map(
            static fn(Relation $relation): array => [
                $relation->source()->value(),
                $relation->kind()->value(),
                $relation->target()->value(),
            ],
            $graph->relations(),
        );
    }
}
