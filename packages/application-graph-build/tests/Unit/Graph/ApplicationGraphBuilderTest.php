<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphBuild\Tests\Unit\Graph;

use Kongono\ApplicationGraphBuild\Build\ApplicationGraphBuilder;
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
use Kongono\ApplicationGraphContract\Schema\FactVisibility;
use Kongono\ApplicationGraphContract\Schema\FieldDisclosure;
use Kongono\ApplicationGraphContract\Schema\FieldName;
use Kongono\ApplicationGraphContract\Vocabulary\NodeType;
use Kongono\ApplicationGraphContract\Vocabulary\RelationType;
use PHPUnit\Framework\TestCase;

final class ApplicationGraphBuilderTest extends TestCase
{
    public function testEmptyGraphAndExactFourByFourSchemaAdmission(): void
    {
        $builder = new ApplicationGraphBuilder();
        $empty = $builder->build([], []);
        self::assertSame(ContractVersion::Version1, $empty->version());
        self::assertSame([], $empty->nodes());
        self::assertSame([], $empty->relations());

        $nodes = [
            self::node('1', NodeType::CompositionContract, ['symbol' => 'Contract']),
            self::node('2', NodeType::CompositionInput, ['lifetime' => 'operation', 'symbol' => 'Input']),
            self::node('3', NodeType::CompositionRoot, ['contract' => 'Contract', 'kind' => 'operation']),
            self::node('4', NodeType::CompositionService, ['class' => 'Service', 'lifetime' => 'operation']),
            self::node('5', NodeType::CompositionService, ['class' => 'Other', 'cleanup' => 'close', 'lifetime' => 'application']),
        ];
        $relations = [
            self::relation('6', RelationType::ConsumesInput, '4', '2'),
            self::relation('7', RelationType::DependsOn, '4', '5'),
            self::relation('8', RelationType::ExposesRoot, '3', '1'),
            self::relation('9', RelationType::ImplementsContract, '4', '1'),
        ];
        $graph = $builder->build($nodes, $relations);
        self::assertCount(5, $graph->nodes());
        self::assertCount(4, $graph->relations());

        $service = $graph->nodes()[3];
        self::assertSame(['class', 'lifetime'], array_map(static fn($field): string => $field->name()->value(), $service->fields()));
        self::assertSame(FieldDisclosure::Protected, $service->fields()[0]->disclosure());
        self::assertSame(FactVisibility::Application, $service->fields()[0]->visibility());
        self::assertTrue($service->fields()[0]->isRequired());
    }

    /** @param array<string, string> $fields */
    public static function node(string $digit, NodeType $type, array $fields): GraphNodeRecord
    {
        $records = [];
        foreach ($fields as $name => $value) {
            $records[] = new GraphFieldRecord(new FieldName($name), $value);
        }
        return new GraphNodeRecord(ContractVersion::Version1, self::nodeIdentity($digit), $type, $records, self::provenance($digit));
    }

    public static function relation(string $digit, RelationType $type, string $source, string $target): GraphRelationRecord
    {
        return new GraphRelationRecord(ContractVersion::Version1, new RelationIdentity(str_repeat($digit, 64)), $type, self::nodeIdentity($source), self::nodeIdentity($target), [], self::provenance($digit));
    }

    public static function nodeIdentity(string $digit): NodeIdentity
    {
        return new NodeIdentity(str_repeat($digit, 64));
    }
    public static function provenance(string $digit): GraphProvenance
    {
        return new GraphProvenance(new ProducerIdentity(str_repeat('a', 64)), new SourceIdentity(str_repeat($digit, 64)), ProducerContract::CompositionV2, DerivationVersion::Version1, str_repeat('b', 64));
    }
}
