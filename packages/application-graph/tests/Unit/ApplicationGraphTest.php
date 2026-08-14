<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraph\Tests\Unit;

use Kongono\ApplicationGraph\Diagnostic\GraphDiagnostic;
use Kongono\ApplicationGraph\Exception\InvalidGraphException;
use Kongono\ApplicationGraph\Model\ApplicationGraph;
use Kongono\ApplicationGraph\Model\Node;
use Kongono\ApplicationGraph\Model\NodeKey;
use Kongono\ApplicationGraph\Model\NodeKind;
use Kongono\ApplicationGraph\Model\Relation;
use Kongono\ApplicationGraph\Model\RelationKind;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
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

    public function testKindSelectorsReturnEmptyListsForEmptyAndAbsentKinds(): void
    {
        $empty = new ApplicationGraph([], []);
        $graph = new ApplicationGraph(
            [$this->node('source', 'present-node'), $this->node('target', 'other-node')],
            [$this->relation('source', 'present-relation', 'target')],
        );

        self::assertSame([], $empty->nodesOfKind(new NodeKind('absent')));
        self::assertSame([], $empty->relationsOfKind(new RelationKind('absent')));
        self::assertSame([], $graph->nodesOfKind(new NodeKind('absent')));
        self::assertSame([], $graph->relationsOfKind(new RelationKind('absent')));
    }

    public function testNodeKindSelectionMatchesExactValueAndPreservesCanonicalOrderAndObjects(): void
    {
        $first = $this->node('a', 'selected');
        $other = $this->node('m', 'other');
        $last = $this->node('z', 'selected');
        $graph = new ApplicationGraph([$last, $other, $first], []);

        $selected = $graph->nodesOfKind(new NodeKind('selected'));

        self::assertSame([$first, $last], $selected);
        self::assertSame(['a', 'z'], array_map(
            static fn(Node $node): string => $node->key()->value(),
            $selected,
        ));
        self::assertSame($first, $selected[0]);
        self::assertSame($last, $selected[1]);
    }

    public function testRelationKindSelectionMatchesExactValueAndPreservesCanonicalOrderAndObjects(): void
    {
        $first = $this->relation('a', 'selected', 'z');
        $other = $this->relation('m', 'other', 'a');
        $last = $this->relation('z', 'selected', 'a');
        $graph = new ApplicationGraph(
            [$this->node('a'), $this->node('m'), $this->node('z')],
            [$last, $other, $first],
        );

        $selected = $graph->relationsOfKind(new RelationKind('selected'));

        self::assertSame([$first, $last], $selected);
        self::assertSame([
            ['a', 'selected', 'z'],
            ['z', 'selected', 'a'],
        ], $this->relationTriplesFrom($selected));
        self::assertSame($first, $selected[0]);
        self::assertSame($last, $selected[1]);
    }

    public function testNodeAndRelationKindDomainsRemainSeparateForEqualLabels(): void
    {
        $node = $this->node('source', 'shared-label');
        $relation = $this->relation('source', 'shared-label', 'target');
        $graph = new ApplicationGraph([$node, $this->node('target', 'other')], [$relation]);

        self::assertSame([$node], $graph->nodesOfKind(new NodeKind('shared-label')));
        self::assertSame([$relation], $graph->relationsOfKind(new RelationKind('shared-label')));
        self::assertSame([], $graph->nodesOfKind(new NodeKind('other-relation-label')));
        self::assertSame([], $graph->relationsOfKind(new RelationKind('other-node-label')));
    }

    public function testKindSelectorsDistinguishCasePrefixNulAndControlValues(): void
    {
        $kinds = ['kind', 'Kind', 'kind-prefix', "kind\0", "kind\x01"];
        $nodes = [];
        $relations = [];

        foreach ($kinds as $index => $kind) {
            $source = sprintf('source-%d', $index);
            $nodes[] = $this->node($source, $kind);
            $relations[] = $this->relation($source, $kind, 'target');
        }

        $nodes[] = $this->node('target', 'target-kind');
        $graph = new ApplicationGraph($nodes, $relations);

        foreach ($kinds as $index => $kind) {
            $selectedNodes = $graph->nodesOfKind(new NodeKind($kind));
            $selectedRelations = $graph->relationsOfKind(new RelationKind($kind));

            self::assertCount(1, $selectedNodes);
            self::assertSame(sprintf('source-%d', $index), $selectedNodes[0]->key()->value());
            self::assertCount(1, $selectedRelations);
            self::assertSame($kind, $selectedRelations[0]->kind()->value());
        }
    }

    public function testKindSelectorOrderIsIndependentOfConstructorInputOrder(): void
    {
        $nodes = [
            $this->node('z', 'selected'),
            $this->node('a', 'selected'),
            $this->node('m', 'other'),
        ];
        $relations = [
            $this->relation('z', 'selected', 'a'),
            $this->relation('a', 'selected', 'z'),
            $this->relation('m', 'other', 'a'),
        ];
        $forward = new ApplicationGraph($nodes, $relations);
        $reverse = new ApplicationGraph(array_reverse($nodes), array_reverse($relations));

        self::assertSame(
            array_map(static fn(Node $node): string => $node->key()->value(), $forward->nodesOfKind(new NodeKind('selected'))),
            array_map(static fn(Node $node): string => $node->key()->value(), $reverse->nodesOfKind(new NodeKind('selected'))),
        );
        self::assertSame(
            $this->relationTriplesFrom($forward->relationsOfKind(new RelationKind('selected'))),
            $this->relationTriplesFrom($reverse->relationsOfKind(new RelationKind('selected'))),
        );
    }

    public function testKindSelectorResultsAreDetachedAndRepeatedCallsRetainNoMutableState(): void
    {
        $node = $this->node('source', 'selected');
        $relation = $this->relation('source', 'selected', 'target');
        $graph = new ApplicationGraph([$node, $this->node('target', 'other')], [$relation]);
        $nodes = $graph->nodesOfKind(new NodeKind('selected'));
        $relations = $graph->relationsOfKind(new RelationKind('selected'));

        array_pop($nodes);
        array_pop($relations);

        self::assertSame([$node], $graph->nodesOfKind(new NodeKind('selected')));
        self::assertSame([$node], $graph->nodesOfKind(new NodeKind('selected')));
        self::assertSame([$relation], $graph->relationsOfKind(new RelationKind('selected')));
        self::assertSame([$relation], $graph->relationsOfKind(new RelationKind('selected')));
        self::assertSame([$node, $graph->node(new NodeKey('target'))], $graph->nodes());
        self::assertSame([$relation], $graph->relations());
    }

    public function testDirectRelationSelectorsContinueToIgnoreRelationKind(): void
    {
        $first = $this->relation('source', 'a', 'target');
        $second = $this->relation('source', 'b', 'target');
        $graph = new ApplicationGraph(
            [$this->node('source'), $this->node('target')],
            [$second, $first],
        );

        self::assertSame([$first, $second], $graph->outgoingRelations(new NodeKey('source')));
        self::assertSame([$first, $second], $graph->incomingRelations(new NodeKey('target')));
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
            'nodesOfKind',
            'outgoingRelations',
            'relations',
            'relationsOfKind',
        ], $methodNames);

        $selectorParameters = [
            'incomingRelations' => NodeKey::class,
            'nodesOfKind' => NodeKind::class,
            'outgoingRelations' => NodeKey::class,
            'relationsOfKind' => RelationKind::class,
        ];

        foreach ($selectorParameters as $methodName => $parameterType) {
            $method = $reflection->getMethod($methodName);
            $parameters = $method->getParameters();

            self::assertCount(1, $parameters);
            self::assertFalse($parameters[0]->isOptional());
            self::assertSame($parameterType, (string) $parameters[0]->getType());
            self::assertSame('array', (string) $method->getReturnType());
        }
    }

    public function testEveryApplicationGraphFailureUsesItsExactDiagnostic(): void
    {
        $node = $this->node('node');

        $this->assertGraphDiagnostic(
            static fn(): object => (new ReflectionClass(ApplicationGraph::class))->newInstance(['node' => $node], []),
            GraphDiagnostic::NodesNotList,
            'Nodes must be supplied as a zero-based consecutive list.',
            [['entry_count', 'Entry count', 'integer', 1, 'safe', null]],
        );
        $this->assertGraphDiagnostic(
            static fn(): object => (new ReflectionClass(ApplicationGraph::class))->newInstance([$node], [1 => new Relation(new NodeKey('node'), new RelationKind('kind'), new NodeKey('node'))]),
            GraphDiagnostic::RelationsNotList,
            'Relations must be supplied as a zero-based consecutive list.',
            [
                ['entry_count', 'Entry count', 'integer', 1, 'safe', null],
                ['first_unexpected_key', 'First unexpected key', 'text', '1', 'protected', false],
            ],
        );
        $this->assertGraphDiagnostic(
            static fn(): object => (new ReflectionClass(ApplicationGraph::class))->newInstance(['not-a-node'], []),
            GraphDiagnostic::InvalidNodeEntry,
            'Nodes list entry at index 0 must be a Node.',
            [
                ['entry_position', 'Entry position', 'integer', 0, 'safe', null],
                ['actual_value_kind', 'Actual value kind', 'text', 'string', 'safe', false],
            ],
        );
        $this->assertGraphDiagnostic(
            static fn(): object => (new ReflectionClass(ApplicationGraph::class))->newInstance([$node], ['not-a-relation']),
            GraphDiagnostic::InvalidRelationEntry,
            'Relations list entry at index 0 must be a Relation.',
            [
                ['entry_position', 'Entry position', 'integer', 0, 'safe', null],
                ['actual_value_kind', 'Actual value kind', 'text', 'string', 'safe', false],
            ],
        );
        $this->assertGraphDiagnostic(
            fn(): ApplicationGraph => new ApplicationGraph([$this->node('duplicate'), $this->node('duplicate')], []),
            GraphDiagnostic::DuplicateNodeKey,
            'Duplicate node key "duplicate" is not allowed.',
            [['node_key', 'Node key', 'text', 'duplicate', 'protected', false]],
        );
        $this->assertGraphDiagnostic(
            fn(): ApplicationGraph => new ApplicationGraph([$this->node('target')], [$this->relation('missing', 'kind', 'target')]),
            GraphDiagnostic::MissingRelationSource,
            'Relation source key "missing" does not reference an existing node.',
            [
                ['relation_position', 'Relation position', 'integer', 0, 'safe', null],
                ['source_key', 'Source key', 'text', 'missing', 'protected', false],
            ],
        );
        $this->assertGraphDiagnostic(
            fn(): ApplicationGraph => new ApplicationGraph([$this->node('source')], [$this->relation('source', 'kind', 'missing')]),
            GraphDiagnostic::MissingRelationTarget,
            'Relation target key "missing" does not reference an existing node.',
            [
                ['relation_position', 'Relation position', 'integer', 0, 'safe', null],
                ['target_key', 'Target key', 'text', 'missing', 'protected', false],
            ],
        );
        $this->assertGraphDiagnostic(
            fn(): ApplicationGraph => new ApplicationGraph(
                [$this->node('source'), $this->node('target')],
                [$this->relation('source', 'kind', 'target'), $this->relation('source', 'kind', 'target')],
            ),
            GraphDiagnostic::DuplicateRelationTriple,
            'Duplicate relation triple "source" -> "kind" -> "target" is not allowed.',
            [
                ['source_key', 'Source key', 'text', 'source', 'protected', false],
                ['relation_kind', 'Relation kind', 'text', 'kind', 'protected', false],
                ['target_key', 'Target key', 'text', 'target', 'protected', false],
            ],
        );
    }

    public function testNonListContextCapturesOnlyTheFirstSafeUnexpectedKey(): void
    {
        $node = $this->node('node');

        foreach ([
            [[2 => $node], '2'],
            [[0 => $node, 2 => $node], '2'],
            [[-1 => $node], '-1'],
        ] as [$nodes, $expectedKey]) {
            $exception = $this->graphFailure(
                static fn(): object => (new ReflectionClass(ApplicationGraph::class))->newInstance($nodes, []),
            );
            $fields = $exception->diagnosticContext()->fields();

            self::assertSame(GraphDiagnostic::NodesNotList->value, $exception->diagnosticId());
            self::assertSame(['entry_count', 'first_unexpected_key'], array_map(static fn($field): string => $field->key(), $fields));
            self::assertSame(count($nodes), $fields[0]->value());
            self::assertSame($expectedKey, $fields[1]->value());
            self::assertSame(DiagnosticDisclosure::Protected, $fields[1]->disclosure());
        }

        foreach ([['secret' => $node], [0 => $node, 'secret' => $node, 2 => $node]] as $nodes) {
            $exception = $this->graphFailure(
                static fn(): object => (new ReflectionClass(ApplicationGraph::class))->newInstance($nodes, []),
            );
            $fields = $exception->diagnosticContext()->fields();

            self::assertSame(['entry_count'], array_map(static fn($field): string => $field->key(), $fields));
            self::assertSame(count($nodes), $fields[0]->value());
            self::assertStringNotContainsString('secret', implode('|', array_map(
                static fn($field): string => (string) $field->value(),
                $fields,
            )));
        }
    }

    public function testInvalidEntryValueKindTokensAreClosedAndNonIdentifying(): void
    {
        $resource = fopen('php://memory', 'r');
        self::assertIsResource($resource);
        $closedResource = fopen('php://memory', 'r');
        self::assertIsResource($closedResource);
        fclose($closedResource);
        $object = new class {};
        $objectClass = $object::class;
        $samples = [
            [null, 'null'],
            [true, 'boolean'],
            [1, 'integer'],
            [1.5, 'float'],
            ['value', 'string'],
            [[], 'array'],
            [$object, 'object'],
            [$resource, 'resource'],
            [$closedResource, 'closed_resource'],
        ];

        foreach ($samples as [$value, $expectedToken]) {
            $exception = $this->graphFailure(
                static fn(): object => (new ReflectionClass(ApplicationGraph::class))->newInstance([$value], []),
            );
            $fields = $exception->diagnosticContext()->fields();

            self::assertSame(GraphDiagnostic::InvalidNodeEntry->value, $exception->diagnosticId());
            self::assertSame('actual_value_kind', $fields[1]->key());
            self::assertSame($expectedToken, $fields[1]->value());
            self::assertSame(DiagnosticDisclosure::Safe, $fields[1]->disclosure());
            self::assertStringNotContainsString($objectClass, $fields[1]->value());
        }

        fclose($resource);
    }

    public function testMissingEndpointContextUsesCallerRelationPositionBeforeSorting(): void
    {
        $nodes = [$this->node('a'), $this->node('z')];
        $sourceFailure = $this->graphFailure(fn(): ApplicationGraph => new ApplicationGraph($nodes, [
            $this->relation('z', 'z', 'a'),
            $this->relation('missing', 'a', 'a'),
        ]));
        $targetFailure = $this->graphFailure(fn(): ApplicationGraph => new ApplicationGraph($nodes, [
            $this->relation('z', 'z', 'a'),
            $this->relation('a', 'a', 'missing'),
        ]));

        self::assertSame(1, $sourceFailure->diagnosticContext()->fields()[0]->value());
        self::assertSame('missing', $sourceFailure->diagnosticContext()->fields()[1]->value());
        self::assertSame(1, $targetFailure->diagnosticContext()->fields()[0]->value());
        self::assertSame('missing', $targetFailure->diagnosticContext()->fields()[1]->value());
    }

    public function testGraphStructuralTextContextUsesBoundedByteRepresentation(): void
    {
        $longKey = str_repeat('k', 600);
        $longFailure = $this->graphFailure(fn(): ApplicationGraph => new ApplicationGraph([
            $this->node($longKey),
            $this->node($longKey),
        ], []));
        $longField = $longFailure->diagnosticContext()->fields()[0];
        $binaryKey = "key\0\xFF";
        $binaryFailure = $this->graphFailure(fn(): ApplicationGraph => new ApplicationGraph([
            $this->node($binaryKey),
            $this->node($binaryKey),
        ], []));
        $binaryField = $binaryFailure->diagnosticContext()->fields()[0];

        self::assertSame(512, strlen((string) $longField->value()));
        self::assertStringEndsWith('...[truncated]', (string) $longField->value());
        self::assertTrue($longField->textWasTruncated());
        self::assertSame('key\\x00\\xFF', $binaryField->value());
        self::assertFalse($binaryField->textWasTruncated());
        self::assertStringContainsString($longKey, $longFailure->getMessage());
        self::assertStringContainsString($binaryKey, $binaryFailure->getMessage());
    }

    public function testMultipleInvalidInputsRetainExistingFailurePriority(): void
    {
        $node = $this->node('node');
        $relation = $this->relation('missing', 'kind', 'also-missing');

        self::assertSame(GraphDiagnostic::NodesNotList->value, $this->graphFailure(
            static fn(): object => (new ReflectionClass(ApplicationGraph::class))->newInstance(['node' => 'invalid'], ['relation' => 'invalid']),
        )->diagnosticId());
        self::assertSame(GraphDiagnostic::RelationsNotList->value, $this->graphFailure(
            static fn(): object => (new ReflectionClass(ApplicationGraph::class))->newInstance(['invalid'], ['relation' => 'invalid']),
        )->diagnosticId());
        self::assertSame(GraphDiagnostic::InvalidNodeEntry->value, $this->graphFailure(
            static fn(): object => (new ReflectionClass(ApplicationGraph::class))->newInstance(['invalid'], ['invalid']),
        )->diagnosticId());
        self::assertSame(GraphDiagnostic::InvalidRelationEntry->value, $this->graphFailure(
            static fn(): object => (new ReflectionClass(ApplicationGraph::class))->newInstance([$node], ['invalid']),
        )->diagnosticId());
        self::assertSame(GraphDiagnostic::DuplicateNodeKey->value, $this->graphFailure(
            fn(): ApplicationGraph => new ApplicationGraph([$this->node('duplicate'), $this->node('duplicate')], [$relation]),
        )->diagnosticId());
        self::assertSame(GraphDiagnostic::MissingRelationSource->value, $this->graphFailure(
            fn(): ApplicationGraph => new ApplicationGraph([$node], [$relation]),
        )->diagnosticId());
    }

    /**
     * @param callable(): object                                                $operation
     * @param list<array{string, string, string, string|int|bool, string, ?bool}> $expectedFields
     */
    private function assertGraphDiagnostic(
        callable $operation,
        GraphDiagnostic $diagnostic,
        string $message,
        array $expectedFields,
    ): void {
        try {
            $operation();
            self::fail('Invalid graph construction must fail.');
        } catch (InvalidGraphException $exception) {
            self::assertSame($diagnostic->value, $exception->diagnosticId());
            self::assertSame($diagnostic->title(), $exception->diagnosticTitle());
            self::assertSame($diagnostic->remediation(), $exception->diagnosticRemediation());
            self::assertSame($message, $exception->getMessage());
            self::assertSame(0, $exception->getCode());
            self::assertNull($exception->getPrevious());
            self::assertSame($diagnostic->contextSchema()->operation(), $exception->diagnosticContext()->operation());
            self::assertSame($diagnostic->contextSchema()->violatedInvariant(), $exception->diagnosticContext()->violatedInvariant());
            self::assertSame($expectedFields, array_map(
                static fn($field): array => [
                    $field->key(),
                    $field->label(),
                    $field->kind()->value,
                    $field->value(),
                    $field->disclosure()->value,
                    $field->textWasTruncated(),
                ],
                $exception->diagnosticContext()->fields(),
            ));
        }
    }

    /** @param callable(): object $operation */
    private function graphFailure(callable $operation): InvalidGraphException
    {
        try {
            $operation();
            self::fail('Invalid graph construction must fail.');
        } catch (InvalidGraphException $exception) {
            return $exception;
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
