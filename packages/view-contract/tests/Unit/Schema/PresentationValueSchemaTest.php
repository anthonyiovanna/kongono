<?php

declare(strict_types=1);

namespace Kongono\ViewContract\Tests\Unit\Schema;

use Kongono\ViewContract\Exception\InvalidViewContractException;
use Kongono\ViewContract\Schema\PresentationFieldSchema;
use Kongono\ViewContract\Schema\PresentationValueKind;
use Kongono\ViewContract\Schema\PresentationValueSchema;
use Kongono\ViewContract\Value\IntegerValue;
use Kongono\ViewContract\Value\ListValue;
use Kongono\ViewContract\Value\PresentationValue;
use Kongono\ViewContract\Value\RecordValue;
use Kongono\ViewContract\Value\StringValue;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class PresentationValueSchemaTest extends TestCase
{
    public function testFactoriesExposeExactScalarAndListShape(): void
    {
        $string = PresentationValueSchema::string(12);
        self::assertSame(PresentationValueKind::String, $string->kind());
        self::assertSame(12, $string->maxBytes());
        self::assertNull($string->itemSchema());
        self::assertTrue($string->matches(new StringValue('twelve')));
        self::assertFalse($string->matches(new IntegerValue(12)));

        $list = PresentationValueSchema::list($string, 2);
        self::assertSame(2, $list->maxItems());
        self::assertTrue($list->matches(new ListValue([new StringValue('a'), new StringValue('b')])));
        self::assertFalse($list->matches(new ListValue([new StringValue('a'), new StringValue('b'), new StringValue('c')])));
    }

    public function testRecordFieldsAreListUniqueCanonicalAndDetached(): void
    {
        $fields = [
            new PresentationFieldSchema('z', PresentationValueSchema::integer(), false),
            new PresentationFieldSchema('a', PresentationValueSchema::string(), true),
        ];
        $schema = PresentationValueSchema::record($fields);
        $fields[] = new PresentationFieldSchema('later', PresentationValueSchema::boolean(), false);
        $returned = $schema->fields();
        $returned[] = new PresentationFieldSchema('later', PresentationValueSchema::boolean(), false);
        self::assertSame(['a', 'z'], array_map(static fn(PresentationFieldSchema $field): string => $field->name(), $schema->fields()));
        self::assertTrue($schema->equals(PresentationValueSchema::record([
            new PresentationFieldSchema('a', PresentationValueSchema::string(), true),
            new PresentationFieldSchema('z', PresentationValueSchema::integer(), false),
        ])));
        self::assertTrue($schema->matches(new RecordValue(['a' => new StringValue('value')])));
    }

    public function testSchemaRejectsRangeShapeDuplicateAndDepthViolations(): void
    {
        $invalid = [
            static fn() => PresentationValueSchema::string(-1),
            static fn() => PresentationValueSchema::string(65537),
            static fn() => PresentationValueSchema::list(PresentationValueSchema::integer(), -1),
            static fn() => PresentationValueSchema::list(PresentationValueSchema::integer(), 257),
            static fn() => PresentationValueSchema::record(['bad' => new PresentationFieldSchema('a', PresentationValueSchema::integer(), true)]),
            static fn() => PresentationValueSchema::record([
                new PresentationFieldSchema('a', PresentationValueSchema::integer(), true),
                new PresentationFieldSchema('a', PresentationValueSchema::integer(), false),
            ]),
        ];
        foreach ($invalid as $create) {
            try {
                $create();
                self::fail('Invalid schema was accepted.');
            } catch (InvalidViewContractException) {
            }
        }

        $schema = PresentationValueSchema::integer();
        for ($depth = 2; $depth <= 16; ++$depth) {
            $schema = PresentationValueSchema::list($schema);
        }
        self::assertSame(16, $schema->depth());
        $this->expectException(InvalidViewContractException::class);
        PresentationValueSchema::list($schema);
    }

    public function testAggregateSchemaNodeBoundIsExact(): void
    {
        $childFields = [];
        for ($position = 0; $position < 31; ++$position) {
            $childFields[] = new PresentationFieldSchema('child_' . $position, PresentationValueSchema::integer(), false);
        }
        $child = PresentationValueSchema::record($childFields);
        self::assertSame(32, $child->nodeCount());

        $rootFields = [];
        for ($position = 0; $position < 127; ++$position) {
            $rootFields[] = new PresentationFieldSchema('group_' . $position, $child, false);
        }
        $lastChild = PresentationValueSchema::record(array_slice($childFields, 0, 30));
        $rootFields[] = new PresentationFieldSchema('group_127', $lastChild, false);
        self::assertSame(4096, PresentationValueSchema::record($rootFields)->nodeCount());

        $rootFields[127] = new PresentationFieldSchema('group_127', $child, false);
        $this->expectException(InvalidViewContractException::class);
        PresentationValueSchema::record($rootFields);
    }

    public function testDirectScalarSchemasRejectForeignValuesBeforeInvokingThem(): void
    {
        $foreign = new class implements PresentationValue {
            public function kind(): PresentationValueKind
            {
                throw new RuntimeException('foreign-object-content');
            }

            public function equals(PresentationValue $other): bool
            {
                throw new RuntimeException('foreign-object-content');
            }

            public function depth(): int
            {
                throw new RuntimeException('foreign-object-content');
            }

            public function nodeCount(): int
            {
                throw new RuntimeException('foreign-object-content');
            }

            public function __toString(): string
            {
                return 'foreign-string-content';
            }
        };

        foreach ([
            PresentationValueSchema::integer(),
            PresentationValueSchema::boolean(),
            PresentationValueSchema::nullValue(),
            PresentationValueSchema::string(),
        ] as $schema) {
            self::assertFalse($schema->matches($foreign));
            try {
                $schema->validate($foreign);
                self::fail('Foreign presentation value was accepted.');
            } catch (InvalidViewContractException $failure) {
                self::assertSame('KGN-VIEW-CONTRACT-1804', $failure->diagnosticId());
                $fields = $failure->diagnosticContext()->fields();
                self::assertCount(1, $fields);
                self::assertSame('violation', $fields[0]->key());
                self::assertSame('unsupported_value_implementation', $fields[0]->value());
                foreach (['foreign-object-content', 'foreign-string-content'] as $private) {
                    self::assertStringNotContainsString($private, $failure->getMessage());
                    self::assertStringNotContainsString($private, (string) $failure);
                    foreach ($fields as $field) {
                        self::assertStringNotContainsString($private, (string) $field->value());
                    }
                }
            }
        }
    }
}
