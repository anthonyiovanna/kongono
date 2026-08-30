<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphContract\Tests\Unit\Schema;

use Kongono\ApplicationGraphContract\ContractVersion;
use Kongono\ApplicationGraphContract\Exception\InvalidGraphContractException;
use Kongono\ApplicationGraphContract\Schema\FactVisibility;
use Kongono\ApplicationGraphContract\Schema\FieldDisclosure;
use Kongono\ApplicationGraphContract\Schema\FieldName;
use Kongono\ApplicationGraphContract\Schema\FieldSchema;
use Kongono\ApplicationGraphContract\Schema\FieldType;
use Kongono\ApplicationGraphContract\Schema\NodeSchema;
use Kongono\ApplicationGraphContract\Schema\RelationSchema;
use Kongono\ApplicationGraphContract\Schema\SchemaCatalog;
use Kongono\ApplicationGraphContract\Vocabulary\NodeType;
use Kongono\ApplicationGraphContract\Vocabulary\RelationType;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class SchemaTest extends TestCase
{
    public function testFieldNameGrammarAndBoundaryAreExact(): void
    {
        foreach (['a', 'a0_b', 'a' . str_repeat('0', 63)] as $accepted) {
            self::assertSame($accepted, (new FieldName($accepted))->value());
        }

        foreach (['', '_a', 'A', 'a-', 'a ' . str_repeat('0', 62), 'é', 'a' . str_repeat('0', 64)] as $rejected) {
            try {
                new FieldName($rejected);
                self::fail('Invalid field name was accepted.');
            } catch (InvalidGraphContractException $exception) {
                self::assertSame('KGN-GRAPH-1102', $exception->diagnosticId());
                if (strlen($rejected) > 4) {
                    self::assertStringNotContainsString($rejected, $exception->getMessage());
                }
            }
        }
    }

    public function testFieldNodeAndRelationSchemasAreReadonlyAndDetached(): void
    {
        $class = self::field('class');
        $lifetime = self::field('lifetime');
        $input = [$class, $lifetime];
        $node = new NodeSchema(NodeType::CompositionService, $input);
        $input[] = self::field('symbol');
        $returned = $node->fields();
        array_pop($returned);

        self::assertSame([$class, $lifetime], $node->fields());
        self::assertTrue((new ReflectionClass($class))->isReadOnly());
        self::assertTrue((new ReflectionClass($node))->isReadOnly());

        $relation = new RelationSchema(
            RelationType::DependsOn,
            [NodeType::CompositionService],
            [NodeType::CompositionService],
            [],
        );
        $sources = $relation->allowedSources();
        $targets = $relation->allowedTargets();
        $sources[] = NodeType::CompositionRoot;
        $targets = [];
        self::assertSame([NodeType::CompositionService], $relation->allowedSources());
        self::assertSame([NodeType::CompositionService], $relation->allowedTargets());
        self::assertTrue((new ReflectionClass($relation))->isReadOnly());
        self::assertSame([], $targets);
    }

    public function testMalformedNodeSchemaListsFailDeterministically(): void
    {
        $valid = self::field('a');

        foreach ([
            ['fields_not_list', ['name' => $valid]],
            ['invalid_field_entry', [$valid, 'wrong']],
            ['duplicate_field_name', [$valid, self::field('a')]],
            ['non_canonical_field_order', [self::field('b'), $valid]],
        ] as [$violation, $fields]) {
            try {
                new NodeSchema(NodeType::CompositionInput, $fields);
                self::fail('Malformed node schema was accepted.');
            } catch (InvalidGraphContractException $exception) {
                self::assertSame('KGN-GRAPH-1103', $exception->diagnosticId());
                self::assertSame($violation, $exception->diagnosticContext()->fields()[0]->value());
            }
        }

        try {
            new NodeSchema(NodeType::CompositionInput, array_fill(0, 33, $valid));
            self::fail('Oversized node schema was accepted.');
        } catch (InvalidGraphContractException $exception) {
            self::assertSame('KGN-GRAPH-1105', $exception->diagnosticId());
        }
    }

    public function testMalformedRelationSchemaListsFailDeterministically(): void
    {
        $cases = [
            [['source' => NodeType::CompositionService], [NodeType::CompositionInput], [], 'node_types_not_list'],
            [[], [NodeType::CompositionInput], [], 'required_endpoints_empty'],
            [[NodeType::CompositionService], [], [], 'required_endpoints_empty'],
            [[NodeType::CompositionService, 'wrong'], [NodeType::CompositionInput], [], 'invalid_node_type_entry'],
            [[NodeType::CompositionService, NodeType::CompositionService], [NodeType::CompositionInput], [], 'duplicate_node_type'],
            [[NodeType::CompositionService, NodeType::CompositionInput], [NodeType::CompositionInput], [], 'non_canonical_node_type_order'],
        ];

        foreach ($cases as [$sources, $targets, $fields, $violation]) {
            try {
                new RelationSchema(RelationType::ConsumesInput, $sources, $targets, $fields);
                self::fail('Malformed relation schema was accepted.');
            } catch (InvalidGraphContractException $exception) {
                self::assertSame('KGN-GRAPH-1103', $exception->diagnosticId());
                self::assertSame($violation, $exception->diagnosticContext()->fields()[0]->value());
            }
        }

        try {
            new RelationSchema(
                RelationType::ConsumesInput,
                array_fill(0, 17, NodeType::CompositionInput),
                [NodeType::CompositionInput],
                [],
            );
            self::fail('Oversized endpoint list was accepted.');
        } catch (InvalidGraphContractException $exception) {
            self::assertSame('KGN-GRAPH-1105', $exception->diagnosticId());
        }
    }

    public function testVersionOneCatalogIsExactCanonicalAndFresh(): void
    {
        $first = SchemaCatalog::version1();
        $second = SchemaCatalog::version1();

        self::assertNotSame($first, $second);
        self::assertSame(ContractVersion::Version1, $first->version());
        self::assertSame([
            ['composition.contract', [['symbol', true, 'text', 'protected', 'application']]],
            ['composition.input', [
                ['lifetime', true, 'text', 'safe', 'application'],
                ['symbol', true, 'text', 'protected', 'application'],
            ]],
            ['composition.root', [
                ['contract', true, 'text', 'protected', 'application'],
                ['kind', true, 'text', 'safe', 'application'],
            ]],
            ['composition.service', [
                ['class', true, 'text', 'protected', 'application'],
                ['cleanup', false, 'text', 'protected', 'application'],
                ['lifetime', true, 'text', 'safe', 'application'],
            ]],
        ], array_map(static fn(NodeSchema $schema): array => [
            $schema->type()->value,
            array_map(self::fieldProjection(...), $schema->fields()),
        ], $first->nodeSchemas()));

        self::assertSame([
            ['composition.consumes_input', ['composition.service'], ['composition.input'], []],
            ['composition.depends_on', ['composition.service'], ['composition.service'], []],
            ['composition.exposes_root', ['composition.root'], ['composition.contract'], []],
            ['composition.implements_contract', ['composition.service'], ['composition.contract'], []],
        ], array_map(static fn(RelationSchema $schema): array => [
            $schema->type()->value,
            array_map(static fn(NodeType $type): string => $type->value, $schema->allowedSources()),
            array_map(static fn(NodeType $type): string => $type->value, $schema->allowedTargets()),
            $schema->fields(),
        ], $first->relationSchemas()));

        foreach (NodeType::cases() as $type) {
            self::assertSame($type, $first->nodeSchema($type)->type());
        }
        foreach (RelationType::cases() as $type) {
            self::assertSame($type, $first->relationSchema($type)->type());
        }
    }

    private static function field(string $name): FieldSchema
    {
        return new FieldSchema(
            new FieldName($name),
            FieldType::Text,
            true,
            FieldDisclosure::Safe,
            FactVisibility::Application,
        );
    }

    /** @return array{string, bool, string, string, string} */
    private static function fieldProjection(FieldSchema $field): array
    {
        return [
            $field->name()->value(),
            $field->isRequired(),
            $field->type()->value,
            $field->disclosure()->value,
            $field->visibility()->value,
        ];
    }
}
