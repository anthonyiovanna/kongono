<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphBuild\Tests\Unit\Graph;

use Kongono\ApplicationGraphBuild\Build\ApplicationGraphBuilder;
use Kongono\ApplicationGraphBuild\Exception\InvalidGraphBuildException;
use Kongono\ApplicationGraphContract\Vocabulary\NodeType;
use Kongono\ApplicationGraphContract\Vocabulary\RelationType;
use PHPUnit\Framework\TestCase;

final class ApplicationGraphValidationTest extends TestCase
{
    public function testInputListsTypesAndBoundsFailClosed(): void
    {
        $builder = new ApplicationGraphBuilder();
        foreach ([
            [['node' => ApplicationGraphBuilderTest::node('1', NodeType::CompositionContract, ['symbol' => 'x'])], [], 'KGN-GRAPH-1106'],
            [[], ['relation' => 'raw'], 'KGN-GRAPH-1106'],
            [['raw'], [], 'KGN-GRAPH-1106'],
            [[], ['raw'], 'KGN-GRAPH-1106'],
            [array_fill(0, ApplicationGraphBuilder::MAX_NODES + 1, 'raw'), [], 'KGN-GRAPH-1110'],
            [[], array_fill(0, ApplicationGraphBuilder::MAX_RELATIONS + 1, 'raw'), 'KGN-GRAPH-1110'],
        ] as [$nodes, $relations, $id]) {
            try {
                $builder->build($nodes, $relations);
                self::fail('Invalid input was accepted.');
            } catch (InvalidGraphBuildException $exception) {
                self::assertSame($id, $exception->diagnosticId());
            }
        }
    }

    public function testFieldIdentityTupleAndEndpointFailuresAreExact(): void
    {
        $builder = new ApplicationGraphBuilder();
        $contract = ApplicationGraphBuilderTest::node('1', NodeType::CompositionContract, ['symbol' => 'x']);
        $service = ApplicationGraphBuilderTest::node('2', NodeType::CompositionService, ['class' => 'S', 'lifetime' => 'operation']);

        $cases = [
            [[ApplicationGraphBuilderTest::node('3', NodeType::CompositionContract, [])], [], 'KGN-GRAPH-1107'],
            [[ApplicationGraphBuilderTest::node('3', NodeType::CompositionContract, ['symbol' => 'x', 'unknown' => 'y'])], [], 'KGN-GRAPH-1107'],
            [[$contract, $contract], [], 'KGN-GRAPH-1108'],
            [[$contract, $service], [ApplicationGraphBuilderTest::relation('4', RelationType::ImplementsContract, '2', '1'), ApplicationGraphBuilderTest::relation('4', RelationType::DependsOn, '2', '2')], 'KGN-GRAPH-1108'],
            [[$contract, $service], [ApplicationGraphBuilderTest::relation('4', RelationType::ImplementsContract, '2', '1'), ApplicationGraphBuilderTest::relation('5', RelationType::ImplementsContract, '2', '1')], 'KGN-GRAPH-1108'],
            [[$contract], [ApplicationGraphBuilderTest::relation('4', RelationType::ImplementsContract, '2', '1')], 'KGN-GRAPH-1109'],
            [[$service], [ApplicationGraphBuilderTest::relation('4', RelationType::ImplementsContract, '2', '1')], 'KGN-GRAPH-1109'],
            [[$contract, $service], [ApplicationGraphBuilderTest::relation('4', RelationType::ImplementsContract, '1', '2')], 'KGN-GRAPH-1109'],
        ];
        foreach ($cases as [$nodes, $relations, $id]) {
            try {
                $builder->build($nodes, $relations);
                self::fail('Invalid graph was accepted.');
            } catch (InvalidGraphBuildException $exception) {
                self::assertSame($id, $exception->diagnosticId());
            }
        }
    }

    public function testRelationFreeNodesSelfDependencyAndCyclesAreValid(): void
    {
        $nodes = [
            ApplicationGraphBuilderTest::node('1', NodeType::CompositionService, ['class' => 'A', 'lifetime' => 'operation']),
            ApplicationGraphBuilderTest::node('2', NodeType::CompositionService, ['class' => 'B', 'lifetime' => 'operation']),
            ApplicationGraphBuilderTest::node('3', NodeType::CompositionInput, ['lifetime' => 'operation', 'symbol' => 'I']),
        ];
        $relations = [
            ApplicationGraphBuilderTest::relation('4', RelationType::DependsOn, '1', '1'),
            ApplicationGraphBuilderTest::relation('5', RelationType::DependsOn, '1', '2'),
            ApplicationGraphBuilderTest::relation('6', RelationType::DependsOn, '2', '1'),
        ];
        $graph = (new ApplicationGraphBuilder())->build($nodes, $relations);
        self::assertCount(3, $graph->nodes());
        self::assertCount(3, $graph->relations());
    }
}
