<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponses\Tests\Unit\Profile;

use Kongono\ModelInvocationContract\Result\ActionName;
use Kongono\ModelProviderOpenAiResponses\Profile\OpenAiResponsesFunctionDefinition;
use Kongono\ModelProviderOpenAiResponses\Schema\StrictJsonSchema;
use PHPUnit\Framework\TestCase;

final class OpenAiResponsesFunctionDefinitionTest extends TestCase
{
    public function testDefinitionCarriesOnlyInertNameAndStrictParameters(): void
    {
        $schema = new StrictJsonSchema('{"type":"object","properties":{"answer":{"type":"string"}},"required":["answer"],"additionalProperties":false}');
        $definition = new OpenAiResponsesFunctionDefinition(new ActionName('record_answer'), $schema);
        self::assertSame('record_answer', $definition->name()->value());
        self::assertSame($schema, $definition->parameters());
        self::assertTrue($definition->equals(new OpenAiResponsesFunctionDefinition(new ActionName('record_answer'), new StrictJsonSchema($schema->canonicalJson()))));
        self::assertNotContains('invoke', get_class_methods($definition));
    }
}
