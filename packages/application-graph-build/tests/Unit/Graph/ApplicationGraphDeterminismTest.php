<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphBuild\Tests\Unit\Graph;

use Kongono\ApplicationGraphBuild\Build\ApplicationGraphBuilder;
use Kongono\ApplicationGraphBuild\Graph\AuthoritativeApplicationGraph;
use Kongono\ApplicationGraphBuild\Graph\GraphField;
use Kongono\ApplicationGraphBuild\Graph\GraphNode;
use Kongono\ApplicationGraphBuild\Graph\GraphRelation;
use Kongono\ApplicationGraphContract\Vocabulary\NodeType;
use Kongono\ApplicationGraphContract\Vocabulary\RelationType;
use PHPUnit\Framework\TestCase;

final class ApplicationGraphDeterminismTest extends TestCase
{
    public function testFixedPermutationsProduceIdenticalBinaryOrderedFreshFacts(): void
    {
        $nodes = [
            ApplicationGraphBuilderTest::node('b', NodeType::CompositionContract, ['symbol' => 'C']),
            ApplicationGraphBuilderTest::node('1', NodeType::CompositionService, ['class' => 'S', 'lifetime' => 'operation']),
            ApplicationGraphBuilderTest::node('a', NodeType::CompositionInput, ['lifetime' => 'operation', 'symbol' => 'I']),
        ];
        $relations = [
            ApplicationGraphBuilderTest::relation('d', RelationType::ImplementsContract, '1', 'b'),
            ApplicationGraphBuilderTest::relation('c', RelationType::ConsumesInput, '1', 'a'),
        ];
        $builder = new ApplicationGraphBuilder();
        $first = $builder->build($nodes, $relations);
        $second = $builder->build([$nodes[2], $nodes[0], $nodes[1]], [$relations[1], $relations[0]]);

        $project = static fn(AuthoritativeApplicationGraph $graph): array => [
            array_map(static fn(GraphNode $node): array => [$node->identity()->value(), $node->type()->value, array_map(static fn(GraphField $field): array => [$field->name()->value(), $field->value()], $node->fields())], $graph->nodes()),
            array_map(static fn(GraphRelation $relation): array => [$relation->identity()->value(), $relation->type()->value, $relation->source()->value(), $relation->target()->value()], $graph->relations()),
        ];
        self::assertSame($project($first), $project($second));
        self::assertNotSame($first, $second);
        self::assertNotSame($first->nodes()[0], $second->nodes()[0]);
        self::assertNotSame(spl_object_id($nodes[1]), spl_object_id($first->nodes()[0]));

        $returned = $first->nodes();
        array_pop($returned);
        self::assertCount(3, $first->nodes());
    }

    public function testSequentialNestedAndInterleavedBuildsRetainNoState(): void
    {
        $builder = new ApplicationGraphBuilder();
        $one = $builder->build([ApplicationGraphBuilderTest::node('1', NodeType::CompositionContract, ['symbol' => 'one'])], []);
        $empty = $builder->build([], []);
        $two = (new ApplicationGraphBuilder())->build([ApplicationGraphBuilderTest::node('2', NodeType::CompositionContract, ['symbol' => 'two'])], []);
        $again = $builder->build([ApplicationGraphBuilderTest::node('1', NodeType::CompositionContract, ['symbol' => 'one'])], []);

        self::assertCount(1, $one->nodes());
        self::assertSame([], $empty->nodes());
        self::assertCount(1, $two->nodes());
        self::assertSame($one->nodes()[0]->identity()->value(), $again->nodes()[0]->identity()->value());
        self::assertNotSame($one->nodes()[0], $again->nodes()[0]);
    }
}
