<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationContract\Tests\Unit\Result;

use Kongono\ModelInvocationContract\Exception\InvalidModelInvocationContractException;
use Kongono\ModelInvocationContract\Result\ActionArguments;
use Kongono\ModelInvocationContract\Result\ActionName;
use Kongono\ModelInvocationContract\Result\ActionSuggestion;
use PHPUnit\Framework\TestCase;

final class ActionSuggestionTest extends TestCase
{
    public function testSuggestionPreservesInertBoundedJsonObjectText(): void
    {
        $raw = '{"answer":42}';
        $suggestion = new ActionSuggestion(new ActionName('lookup.item'), new ActionArguments($raw));
        self::assertSame('lookup.item', $suggestion->name()->value());
        self::assertSame($raw, $suggestion->arguments()->value());
        self::assertTrue($suggestion->equals(new ActionSuggestion(new ActionName('lookup.item'), new ActionArguments($raw))));
        self::assertSame(['__construct', 'name', 'arguments', 'equals'], array_map(static fn(\ReflectionMethod $method): string => $method->name, (new \ReflectionClass($suggestion))->getMethods(\ReflectionMethod::IS_PUBLIC)));
        self::assertSame(65536, strlen((new ActionArguments('{"x":"' . str_repeat('a', 65528) . '"}'))->value()));
        self::assertSame("{\n\t\"x\": 1\n}", (new ActionArguments("{\n\t\"x\": 1\n}"))->value());
    }

    public function testNonObjectInvalidAndOversizedJsonFailWithoutDisclosure(): void
    {
        foreach (['[]', 'null', '{invalid}', '{"x":"' . str_repeat('s', 65536) . '"}'] as $raw) {
            try {
                new ActionArguments($raw);
                self::fail('Expected invalid action arguments.');
            } catch (InvalidModelInvocationContractException $exception) {
                self::assertStringNotContainsString($raw, $exception->getMessage());
                self::assertStringNotContainsString($raw, (string) $exception);
            }
        }
    }
}
