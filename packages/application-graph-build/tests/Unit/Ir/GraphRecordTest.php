<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphBuild\Tests\Unit\Ir;

use Kongono\ApplicationGraphBuild\Exception\InvalidGraphBuildException;
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
use PHPUnit\Framework\TestCase;

final class GraphRecordTest extends TestCase
{
    public function testRecordsRemainDetachedClaimsWithExactValues(): void
    {
        $fields = [self::field('class'), self::field('lifetime')];
        $node = new GraphNodeRecord(ContractVersion::Version1, self::node('1'), NodeType::CompositionService, $fields, self::provenance());
        $fields[] = self::field('symbol');
        $returned = $node->fields();
        array_pop($returned);

        self::assertSame(ContractVersion::Version1, $node->version());
        self::assertSame(NodeType::CompositionService, $node->type());
        self::assertCount(2, $node->fields());
        self::assertSame(self::provenance()->sourceSha256(), $node->provenance()->sourceSha256());

        $relation = new GraphRelationRecord(
            ContractVersion::Version1,
            new RelationIdentity(str_repeat('4', 64)),
            RelationType::DependsOn,
            self::node('1'),
            self::node('2'),
            [],
            self::provenance(),
        );
        self::assertSame(RelationType::DependsOn, $relation->type());
        self::assertSame(self::node('1')->value(), $relation->source()->value());
        self::assertSame([], $relation->fields());
    }

    public function testBothRecordTypesRejectMalformedFieldLists(): void
    {
        $valid = self::field('a');
        $cases = [
            ['fields_not_list', ['field' => $valid]],
            ['invalid_field_entry', [$valid, 'raw']],
            ['duplicate_field_name', [$valid, self::field('a')]],
            ['non_canonical_field_order', [self::field('b'), $valid]],
        ];

        foreach ($cases as [$violation, $fields]) {
            foreach (['node', 'relation'] as $kind) {
                try {
                    'node' === $kind
                        ? new GraphNodeRecord(ContractVersion::Version1, self::node('1'), NodeType::CompositionInput, $fields, self::provenance())
                        : new GraphRelationRecord(ContractVersion::Version1, new RelationIdentity(str_repeat('4', 64)), RelationType::DependsOn, self::node('1'), self::node('2'), $fields, self::provenance());
                    self::fail('Malformed fields were accepted.');
                } catch (InvalidGraphBuildException $exception) {
                    self::assertSame($violation, $exception->diagnosticContext()->fields()[0]->value());
                }
            }
        }

        foreach (['node', 'relation'] as $kind) {
            try {
                $fields = array_fill(0, 33, $valid);
                'node' === $kind
                    ? new GraphNodeRecord(ContractVersion::Version1, self::node('1'), NodeType::CompositionInput, $fields, self::provenance())
                    : new GraphRelationRecord(ContractVersion::Version1, new RelationIdentity(str_repeat('4', 64)), RelationType::DependsOn, self::node('1'), self::node('2'), $fields, self::provenance());
                self::fail('Oversized fields were accepted.');
            } catch (InvalidGraphBuildException $exception) {
                self::assertSame('KGN-GRAPH-1110', $exception->diagnosticId());
            }
        }
    }

    private static function field(string $name): GraphFieldRecord
    {
        return new GraphFieldRecord(new FieldName($name), 'value');
    }
    private static function node(string $digit): NodeIdentity
    {
        return new NodeIdentity(str_repeat($digit, 64));
    }
    private static function provenance(): GraphProvenance
    {
        return new GraphProvenance(new ProducerIdentity(str_repeat('a', 64)), new SourceIdentity(str_repeat('b', 64)), ProducerContract::CompositionV2, DerivationVersion::Version1, str_repeat('c', 64));
    }
}
