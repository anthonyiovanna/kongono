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
use ReflectionMethod;
use ReflectionProperty;

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

    public function testSelectorsReturnEmptyListsForEmptyIsolatedAndMissingKeys(): void
    {
        $empty = new ApplicationGraph([], []);
        $isolated = new ApplicationGraph([$this->node('isolated')], []);

        self::assertSame([], $empty->outgoingRelations(new NodeKey('missing')));
        self::assertSame([], $empty->incomingRelations(new NodeKey('missing')));
        self::assertSame([], $isolated->outgoingRelations(new NodeKey('isolated')));
        self::assertSame([], $isolated->incomingRelations(new NodeKey('isolated')));
        self::assertSame([], $isolated->outgoingRelations(new NodeKey('missing')));
        self::assertSame([], $isolated->incomingRelations(new NodeKey('missing')));
    }

    public function testSelectorsMatchExactEndpointValuesDirectionallyAndReturnStoredObjects(): void
    {
        $outgoingA = $this->relation('source', 'a', 'target-a');
        $outgoingB = $this->relation('source', 'b', 'target-b');
        $incoming = $this->relation('other', 'c', 'source');
        $self = $this->relation('source', 'self', 'source');
        $graph = new ApplicationGraph(
            [
                $this->node('source'),
                $this->node('other'),
                $this->node('target-a'),
                $this->node('target-b'),
            ],
            [$self, $incoming, $outgoingB, $outgoingA],
        );

        $outgoing = $graph->outgoingRelations(new NodeKey('source'));
        $incomingRelations = $graph->incomingRelations(new NodeKey('source'));

        self::assertSame([$outgoingA, $outgoingB, $self], $outgoing);
        self::assertSame([$incoming, $self], $incomingRelations);
        self::assertFalse(in_array($incoming, $outgoing, true));
        self::assertFalse(in_array($outgoingA, $incomingRelations, true));
        self::assertSame($outgoingA, $outgoing[0]);
        self::assertSame($self, $incomingRelations[1]);
    }

    public function testOutgoingSelectionPreservesCanonicalKindThenTargetOrder(): void
    {
        $graph = new ApplicationGraph(
            [$this->node('source'), $this->node('a'), $this->node('b')],
            [
                $this->relation('source', 'z', 'a'),
                $this->relation('source', 'a', 'b'),
                $this->relation('source', 'a', 'a'),
            ],
        );

        self::assertSame([
            ['source', 'a', 'a'],
            ['source', 'a', 'b'],
            ['source', 'z', 'a'],
        ], $this->relationTriplesFrom($graph->outgoingRelations(new NodeKey('source'))));
    }

    public function testIncomingSelectionPreservesGlobalSourceKindTargetOrder(): void
    {
        $graph = new ApplicationGraph(
            [$this->node('target'), $this->node('a'), $this->node('z')],
            [
                $this->relation('z', 'a', 'target'),
                $this->relation('a', 'z', 'target'),
                $this->relation('a', 'a', 'target'),
            ],
        );

        self::assertSame([
            ['a', 'a', 'target'],
            ['a', 'z', 'target'],
            ['z', 'a', 'target'],
        ], $this->relationTriplesFrom($graph->incomingRelations(new NodeKey('target'))));
    }

    public function testSelectorOrderIsIndependentOfConstructorInputOrder(): void
    {
        $nodes = [$this->node('source'), $this->node('a'), $this->node('b')];
        $relations = [
            $this->relation('source', 'z', 'a'),
            $this->relation('source', 'a', 'b'),
            $this->relation('a', 'incoming', 'source'),
        ];
        $forward = new ApplicationGraph($nodes, $relations);
        $reverse = new ApplicationGraph(array_reverse($nodes), array_reverse($relations));

        self::assertSame(
            $this->relationTriplesFrom($forward->outgoingRelations(new NodeKey('source'))),
            $this->relationTriplesFrom($reverse->outgoingRelations(new NodeKey('source'))),
        );
        self::assertSame(
            $this->relationTriplesFrom($forward->incomingRelations(new NodeKey('source'))),
            $this->relationTriplesFrom($reverse->incomingRelations(new NodeKey('source'))),
        );
    }

    public function testEmbeddedControlAndPrefixEndpointValuesDoNotCollide(): void
    {
        $keys = ['a', "a\0", "a\x01", 'ab', 'target'];
        $graph = new ApplicationGraph(
            array_map(fn(string $key): Node => $this->node($key), $keys),
            [
                $this->relation('a', 'exact', 'target'),
                $this->relation("a\0", 'nul', 'target'),
                $this->relation("a\x01", 'control', 'target'),
                $this->relation('ab', 'prefix', 'target'),
                $this->relation('target', 'exact', 'a'),
                $this->relation('target', 'nul', "a\0"),
                $this->relation('target', 'control', "a\x01"),
                $this->relation('target', 'prefix', 'ab'),
            ],
        );

        self::assertSame([
            ['a', 'exact', 'target'],
        ], $this->relationTriplesFrom($graph->outgoingRelations(new NodeKey('a'))));
        self::assertSame([
            ['target', 'exact', 'a'],
        ], $this->relationTriplesFrom($graph->incomingRelations(new NodeKey('a'))));
    }

    public function testSelectorResultsAreDetachedAndRepeatedCallsRetainNoMutableState(): void
    {
        $relation = $this->relation('source', 'kind', 'target');
        $graph = new ApplicationGraph(
            [$this->node('source'), $this->node('target')],
            [$relation],
        );
        $outgoing = $graph->outgoingRelations(new NodeKey('source'));
        $incoming = $graph->incomingRelations(new NodeKey('target'));

        array_pop($outgoing);
        array_pop($incoming);

        self::assertSame([$relation], $graph->relations());
        self::assertSame([$relation], $graph->outgoingRelations(new NodeKey('source')));
        self::assertSame([$relation], $graph->outgoingRelations(new NodeKey('source')));
        self::assertSame([$relation], $graph->incomingRelations(new NodeKey('target')));
        self::assertSame([$relation], $graph->incomingRelations(new NodeKey('target')));
    }

    public function testApplicationGraphRetainsExactClassPropertyAndPublicMethodShape(): void
    {
        $reflection = new ReflectionClass(ApplicationGraph::class);

        self::assertTrue($reflection->isFinal());
        self::assertTrue($reflection->isReadOnly());

        $properties = $reflection->getProperties();
        usort(
            $properties,
            static fn(ReflectionProperty $left, ReflectionProperty $right): int => strcmp(
                $left->getName(),
                $right->getName(),
            ),
        );
        self::assertSame(
            ['nodes', 'relations'],
            array_map(static fn(ReflectionProperty $property): string => $property->getName(), $properties),
        );

        foreach ($properties as $property) {
            self::assertTrue($property->isPrivate());
            self::assertFalse($property->isStatic());
            self::assertTrue($property->isReadOnly());
        }

        $methods = array_values(array_filter(
            $reflection->getMethods(ReflectionMethod::IS_PUBLIC),
            static fn(ReflectionMethod $method): bool => ApplicationGraph::class === $method->getDeclaringClass()->getName(),
        ));
        $methodNames = array_map(static fn(ReflectionMethod $method): string => $method->getName(), $methods);
        sort($methodNames);
        self::assertSame([
            '__construct',
            'hasNode',
            'incomingRelations',
            'node',
            'nodes',
            'outgoingRelations',
            'relations',
        ], $methodNames);

        foreach (['incomingRelations', 'outgoingRelations'] as $methodName) {
            $method = $reflection->getMethod($methodName);
            $parameters = $method->getParameters();

            self::assertCount(1, $parameters);
            self::assertFalse($parameters[0]->isOptional());
            self::assertSame(NodeKey::class, (string) $parameters[0]->getType());
            self::assertSame('array', (string) $method->getReturnType());
        }
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
        return $this->relationTriplesFrom($graph->relations());
    }

    /**
     * @param list<Relation> $relations
     *
     * @return list<array{string, string, string}>
     */
    private function relationTriplesFrom(array $relations): array
    {
        return array_map(
            static fn(Relation $relation): array => [
                $relation->source()->value(),
                $relation->kind()->value(),
                $relation->target()->value(),
            ],
            $relations,
        );
    }
}
