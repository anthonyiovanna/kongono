<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponses\Tests\Unit\Profile;

use Kongono\ModelProviderOpenAiResponses\Exception\InvalidOpenAiResponsesProfileException;
use Kongono\ModelProviderOpenAiResponses\Profile\OpenAiResponsesStructuredOutput;
use Kongono\ModelProviderOpenAiResponses\Schema\StrictJsonSchema;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class OpenAiResponsesStructuredOutputTest extends TestCase
{
    public function testDefinitionIsImmutableAndEqualByValue(): void
    {
        $schema = new StrictJsonSchema('{"type":"object","properties":{},"required":[],"additionalProperties":false}');
        $definition = new OpenAiResponsesStructuredOutput('format_17e', $schema);
        self::assertSame('format_17e', $definition->name());
        self::assertSame($schema, $definition->schema());
        self::assertTrue($definition->equals(new OpenAiResponsesStructuredOutput('format_17e', new StrictJsonSchema($schema->canonicalJson()))));
    }

    #[DataProvider('invalidNames')]
    public function testNameGrammarFailsClosed(string $name): void
    {
        $this->expectException(InvalidOpenAiResponsesProfileException::class);
        new OpenAiResponsesStructuredOutput($name, new StrictJsonSchema('{"type":"object","properties":{},"required":[],"additionalProperties":false}'));
    }

    /** @return iterable<string, array{string}> */
    public static function invalidNames(): iterable
    {
        yield 'empty' => [''];
        yield 'dot' => ['invalid.name'];
        yield 'too long' => [str_repeat('a', 65)];
        yield 'secret' => ['secret-like-17e!'];
    }
}
