<?php

declare(strict_types=1);

namespace Kongono\ViewContract\Tests\Unit\Schema;

use Kongono\ViewContract\Exception\InvalidViewContractException;
use Kongono\ViewContract\Schema\PresentationFieldSchema;
use Kongono\ViewContract\Schema\PresentationValueSchema;
use Kongono\ViewContract\Schema\ViewContextSchema;
use Kongono\ViewContract\Value\IntegerValue;
use Kongono\ViewContract\Value\ListValue;
use Kongono\ViewContract\Value\StringValue;
use Kongono\ViewContract\Value\ViewContext;
use PHPUnit\Framework\TestCase;

final class ViewContextSchemaTest extends TestCase
{
    public function testRequiredOptionalNestedAndStringBoundsMatchExactly(): void
    {
        $schema = new ViewContextSchema(PresentationValueSchema::record([
            new PresentationFieldSchema('title', PresentationValueSchema::string(5), true),
            new PresentationFieldSchema('items', PresentationValueSchema::list(PresentationValueSchema::integer(), 2), false),
        ]));
        self::assertTrue($schema->matches(new ViewContext(['title' => new StringValue('hello')])));
        self::assertTrue($schema->matches(new ViewContext(['title' => new StringValue('hi'), 'items' => new ListValue([new IntegerValue(1)])])));
        self::assertFalse($schema->matches(new ViewContext([])));
        self::assertFalse($schema->matches(new ViewContext(['title' => new StringValue('longer')])));
        self::assertFalse($schema->matches(new ViewContext(['title' => new StringValue('hi'), 'items' => new ListValue([new StringValue('wrong')])])));
        self::assertFalse($schema->matches(new ViewContext(['title' => new StringValue('hi'), 'extra' => new StringValue('closed')])));
    }

    public function testThrowingValidationIsNondisclosing(): void
    {
        $schema = new ViewContextSchema(PresentationValueSchema::record([
            new PresentationFieldSchema('declared_private_field', PresentationValueSchema::integer(), true),
        ]));
        try {
            $schema->validate(new ViewContext(['undeclared_private_field' => new StringValue('secret-markup-<script>')]));
            self::fail('Mismatched context was accepted.');
        } catch (InvalidViewContractException $failure) {
            foreach (['declared_private_field', 'undeclared_private_field', 'secret-markup-<script>'] as $private) {
                self::assertStringNotContainsString($private, $failure->getMessage());
                self::assertStringNotContainsString($private, (string) $failure);
            }
        }
    }

    public function testContextSchemaRequiresRecordRoot(): void
    {
        $this->expectException(InvalidViewContractException::class);
        new ViewContextSchema(PresentationValueSchema::string());
    }
}
