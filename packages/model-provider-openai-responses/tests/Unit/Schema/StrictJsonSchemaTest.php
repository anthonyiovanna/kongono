<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponses\Tests\Unit\Schema;

use Kongono\ModelProviderOpenAiResponses\Exception\InvalidStrictJsonSchemaException;
use Kongono\ModelProviderOpenAiResponses\Schema\StrictJsonSchema;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class StrictJsonSchemaTest extends TestCase
{
    public function testCanonicalBytesEqualityAndDetachedStructureAreExact(): void
    {
        $schema = new StrictJsonSchema('{"type":"object","required":["answer"],"properties":{"answer":{"enum":["yes","no"],"type":"string"}},"additionalProperties":false}');
        self::assertSame('{"additionalProperties":false,"properties":{"answer":{"enum":["yes","no"],"type":"string"}},"required":["answer"],"type":"object"}', $schema->canonicalJson());
        self::assertTrue($schema->equals(new StrictJsonSchema($schema->canonicalJson())));
        $detached = $schema->structure();
        $detached['type'] = 'string';
        self::assertSame('object', $schema->structure()['type']);
        self::assertSame('object', $schema->providerValue()->type);
    }

    #[DataProvider('invalidSchemas')]
    public function testMalformedDuplicateUnknownAndNonObjectSchemasFailWithoutDisclosure(string $json): void
    {
        try {
            new StrictJsonSchema($json);
            self::fail('Invalid schema must fail.');
        } catch (InvalidStrictJsonSchemaException $failure) {
            $presentation = $failure->getMessage() . (string) $failure;
            foreach ($failure->diagnosticContext()->fields() as $field) {
                $presentation .= $field->key() . '=' . $field->value();
            }
            self::assertStringNotContainsString('secret-like-17e', $presentation);
            self::assertSame('KGN-OPENAI-RESPONSES-1719', $failure->diagnosticId());
        }
    }

    /** @return iterable<string, array{string}> */
    public static function invalidSchemas(): iterable
    {
        yield 'malformed' => ['{"secret-like-17e":'];
        yield 'invalid utf8' => ["{\"description\":\"secret-like-17e\xFF\"}"];
        yield 'duplicate root' => ['{"type":"object","type":"object","properties":{},"required":[],"additionalProperties":false}'];
        yield 'duplicate nested' => ['{"type":"object","properties":{"answer":{"type":"string","type":"string"}},"required":["answer"],"additionalProperties":false}'];
        yield 'list root' => ['[]'];
        yield 'scalar root' => ['"secret-like-17e"'];
        yield 'unknown keyword' => ['{"type":"object","properties":{},"required":[],"additionalProperties":false,"secret-like-17e":true}'];
        yield 'reference' => ['{"type":"object","properties":{},"required":[],"additionalProperties":false,"$ref":"secret-like-17e"}'];
        yield 'unknown type' => ['{"type":"secret-like-17e","properties":{},"required":[],"additionalProperties":false}'];
        yield 'union' => ['{"type":"object","properties":{},"required":[],"additionalProperties":false,"anyOf":[{"type":"string"}]}'];
        yield 'properties list' => ['{"type":"object","properties":[],"required":[],"additionalProperties":false}'];
        yield 'missing required member' => ['{"type":"object","properties":{},"additionalProperties":false}'];
        yield 'missing required property' => ['{"type":"object","properties":{"answer":{"type":"string"}},"required":[],"additionalProperties":false}'];
        yield 'extra required property' => ['{"type":"object","properties":{},"required":["answer"],"additionalProperties":false}'];
        yield 'duplicate required property' => ['{"type":"object","properties":{"answer":{"type":"string"}},"required":["answer","answer"],"additionalProperties":false}'];
        yield 'open object' => ['{"type":"object","properties":{},"required":[],"additionalProperties":true}'];
        yield 'invalid property name' => ['{"type":"object","properties":{"secret-like-17e!":{"type":"string"}},"required":["secret-like-17e!"],"additionalProperties":false}'];
        yield 'malicious description' => ['{"type":"object","description":"secret-like-17e","properties":{},"required":[],"additionalProperties":"secret-like-17e"}'];
        yield 'malicious enum value' => ['{"type":"object","properties":{"answer":{"type":"integer","enum":["secret-like-17e"]}},"required":["answer"],"additionalProperties":false}'];
        yield 'empty enum' => ['{"type":"object","properties":{"answer":{"type":"string","enum":[]}},"required":["answer"],"additionalProperties":false}'];
        yield 'duplicate enum' => ['{"type":"object","properties":{"answer":{"type":"string","enum":["same","same"]}},"required":["answer"],"additionalProperties":false}'];
        yield 'array without items' => ['{"type":"object","properties":{"answer":{"type":"array"}},"required":["answer"],"additionalProperties":false}'];
    }
}
