<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponses\Tests\Unit\Internal;

use Kongono\ModelProviderOpenAiResponses\Exception\InvalidStrictJsonSchemaException;
use Kongono\ModelProviderOpenAiResponses\Internal\StrictJsonSchemaCodec;
use Kongono\ModelProviderOpenAiResponses\Internal\StrictJsonSchemaValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class StrictJsonSchemaCodecTest extends TestCase
{
    public function testAllSupportedNodeTypesCanonicalizeRecursively(): void
    {
        $json = '{"required":["items","flag","nothing"],"properties":{"nothing":{"type":"null"},"items":{"items":{"type":"number","enum":[1,2.5]},"type":"array"},"flag":{"description":"bounded","type":"boolean"}},"additionalProperties":false,"type":"object"}';
        $decoded = (new StrictJsonSchemaCodec())->decode($json);
        self::assertSame('{"additionalProperties":false,"properties":{"flag":{"description":"bounded","type":"boolean"},"items":{"items":{"enum":[1,2.5],"type":"number"},"type":"array"},"nothing":{"type":"null"}},"required":["items","flag","nothing"],"type":"object"}', $decoded['canonical']);
        self::assertSame('object', $decoded['structure']['type']);
    }

    #[DataProvider('boundedFailures')]
    public function testEverySchemaBoundFailsClosed(string $json): void
    {
        $this->expectException(InvalidStrictJsonSchemaException::class);
        (new StrictJsonSchemaCodec())->decode($json);
    }

    /** @return iterable<string, array{string}> */
    public static function boundedFailures(): iterable
    {
        yield 'source bytes' => [str_repeat(' ', StrictJsonSchemaCodec::MAXIMUM_SOURCE_BYTES + 1)];
        yield 'json depth' => [str_repeat('[', StrictJsonSchemaCodec::MAXIMUM_JSON_DEPTH + 1) . '0' . str_repeat(']', StrictJsonSchemaCodec::MAXIMUM_JSON_DEPTH + 1)];
        yield 'description' => ['{"type":"object","description":"' . str_repeat('x', StrictJsonSchemaValidator::MAXIMUM_DESCRIPTION_BYTES + 1) . '","properties":{},"required":[],"additionalProperties":false}'];
        $properties = [];
        $required = [];
        for ($i = 0; $i <= StrictJsonSchemaValidator::MAXIMUM_PROPERTIES; ++$i) {
            $name = 'p' . $i;
            $properties[$name] = ['type' => 'string'];
            $required[] = $name;
        }
        yield 'properties' => [json_encode(['type' => 'object', 'properties' => $properties, 'required' => $required, 'additionalProperties' => false], JSON_THROW_ON_ERROR)];
        yield 'required entries' => [json_encode(['type' => 'object', 'properties' => [], 'required' => array_fill(0, StrictJsonSchemaValidator::MAXIMUM_REQUIRED + 1, 'x'), 'additionalProperties' => false], JSON_THROW_ON_ERROR)];
        yield 'enum entries' => [json_encode(['type' => 'object', 'properties' => ['v' => ['type' => 'integer', 'enum' => range(0, StrictJsonSchemaValidator::MAXIMUM_ENUM_ENTRIES)]], 'required' => ['v'], 'additionalProperties' => false], JSON_THROW_ON_ERROR)];
        yield 'property name bytes' => [json_encode(['type' => 'object', 'properties' => [str_repeat('p', StrictJsonSchemaValidator::MAXIMUM_PROPERTY_NAME_BYTES + 1) => ['type' => 'string']], 'required' => [str_repeat('p', StrictJsonSchemaValidator::MAXIMUM_PROPERTY_NAME_BYTES + 1)], 'additionalProperties' => false], JSON_THROW_ON_ERROR)];
        $branches = [];
        for ($branch = 0; $branch < 4; ++$branch) {
            $branchProperties = [];
            for ($node = 0; $node < StrictJsonSchemaValidator::MAXIMUM_PROPERTIES; ++$node) {
                $branchProperties['n' . $node] = ['type' => 'string'];
            }
            $branches['branch' . $branch] = ['type' => 'object', 'properties' => $branchProperties, 'required' => array_keys($branchProperties), 'additionalProperties' => false];
        }
        yield 'aggregate nodes' => [json_encode(['type' => 'object', 'properties' => $branches, 'required' => array_keys($branches), 'additionalProperties' => false], JSON_THROW_ON_ERROR)];
        yield 'logical depth' => ['{"type":"object","properties":{"a":{"type":"array","items":{"type":"array","items":{"type":"array","items":{"type":"array","items":{"type":"array","items":{"type":"array","items":{"type":"array","items":{"type":"array","items":{"type":"string"}}}}}}}}}},"required":["a"],"additionalProperties":false}'];
    }
}
