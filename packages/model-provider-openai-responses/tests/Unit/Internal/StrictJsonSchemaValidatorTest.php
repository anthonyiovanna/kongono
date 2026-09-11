<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponses\Tests\Unit\Internal;

use Kongono\ModelProviderOpenAiResponses\Exception\InvalidStrictJsonSchemaException;
use Kongono\ModelProviderOpenAiResponses\Internal\StrictJsonSchemaValidator;
use Kongono\ModelProviderOpenAiResponses\Schema\StrictJsonSchema;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class StrictJsonSchemaValidatorTest extends TestCase
{
    public function testAcceptedValueIsCanonicalWithoutCoercion(): void
    {
        $schema = new StrictJsonSchema('{"type":"object","properties":{"count":{"type":"integer"},"ratio":{"type":"number"},"tags":{"type":"array","items":{"type":"string"}}},"required":["count","ratio","tags"],"additionalProperties":false}');
        $value = json_decode('{"tags":["b","a"],"ratio":1.5,"count":2}', false, 16, JSON_THROW_ON_ERROR);
        self::assertSame('{"count":2,"ratio":1.5,"tags":["b","a"]}', (new StrictJsonSchemaValidator())->validateValue($value, $schema));
    }

    #[DataProvider('invalidValues')]
    public function testTypesKeySetsEnumsAndAggregateBoundsFailClosed(string $json): void
    {
        $schema = new StrictJsonSchema('{"type":"object","properties":{"count":{"type":"integer"},"kind":{"type":"string","enum":["a","b"]}},"required":["count","kind"],"additionalProperties":false}');
        $this->expectException(InvalidStrictJsonSchemaException::class);
        (new StrictJsonSchemaValidator())->validateValue(json_decode($json, false, 16, JSON_THROW_ON_ERROR), $schema);
    }

    /** @return iterable<string, array{string}> */
    public static function invalidValues(): iterable
    {
        yield 'missing' => ['{"count":1}'];
        yield 'extra' => ['{"count":1,"kind":"a","extra":true}'];
        yield 'string integer' => ['{"count":"1","kind":"a"}'];
        yield 'boolean integer' => ['{"count":true,"kind":"a"}'];
        yield 'float integer' => ['{"count":1.0,"kind":"a"}'];
        yield 'boolean number' => ['{"count":true,"kind":"a"}'];
        yield 'enum mismatch' => ['{"count":1,"kind":"c"}'];
    }

    public function testAggregateValueNodesAreBounded(): void
    {
        $schema = new StrictJsonSchema('{"type":"object","properties":{"items":{"type":"array","items":{"type":"integer"}}},"required":["items"],"additionalProperties":false}');
        $value = json_decode(json_encode(['items' => array_fill(0, StrictJsonSchemaValidator::MAXIMUM_NODES, 1)], JSON_THROW_ON_ERROR), false, 16, JSON_THROW_ON_ERROR);
        $this->expectException(InvalidStrictJsonSchemaException::class);
        (new StrictJsonSchemaValidator())->validateValue($value, $schema);
    }

    public function testNumberAcceptsJsonIntegersAndFloatsButNeverBoolean(): void
    {
        $schema = new StrictJsonSchema('{"type":"object","properties":{"value":{"type":"number"}},"required":["value"],"additionalProperties":false}');
        $validator = new StrictJsonSchemaValidator();
        self::assertSame('{"value":1}', $validator->validateValue(json_decode('{"value":1}'), $schema));
        self::assertSame('{"value":1.5}', $validator->validateValue(json_decode('{"value":1.5}'), $schema));

        $this->expectException(InvalidStrictJsonSchemaException::class);
        $validator->validateValue(json_decode('{"value":true}'), $schema);
    }
}
