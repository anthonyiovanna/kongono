<?php

declare(strict_types=1);

namespace Kongono\ConsoleRuntime\Tests\Unit;

use Kongono\Console\CommandHandler;
use Kongono\Console\CommandIdentifier;
use Kongono\Console\CommandOperation;
use Kongono\Console\CommandResult;
use Kongono\ConsoleRuntime\CommandHandlerBinding;
use Kongono\ConsoleRuntime\CommandHandlerSet;
use Kongono\ConsoleRuntime\Exception\InvalidCommandRuntimeValueException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

final class CommandHandlerSetTest extends TestCase
{
    public function testBindingsAreDetachedSortedAndResolvedExactly(): void
    {
        $bindings = [$this->binding('z-last'), $this->binding('a-first'), $this->binding('middle')];
        $set = new CommandHandlerSet($bindings);
        $bindings[0] = $this->binding('changed');

        self::assertSame(['a-first', 'middle', 'z-last'], array_map(
            static fn(CommandHandlerBinding $binding): string => $binding->identifier()->value(),
            $set->bindings(),
        ));
        self::assertSame('middle', $set->binding(new CommandIdentifier('middle'))?->identifier()->value());
        self::assertTrue($set->hasBinding(new CommandIdentifier('z-last')));
        self::assertFalse($set->hasBinding(new CommandIdentifier('absent')));
        self::assertNull($set->binding(new CommandIdentifier('absent')));

        $public = array_map(
            static fn(ReflectionMethod $method): string => $method->getName(),
            (new ReflectionClass(CommandHandlerSet::class))->getMethods(ReflectionMethod::IS_PUBLIC),
        );
        self::assertSame(['__construct', 'bindings', 'binding', 'hasBinding'], $public);
    }

    public function testOneThroughTwoHundredFiftySixBindingsAreAccepted(): void
    {
        $bindings = [];
        for ($index = 0; $index < 256; ++$index) {
            $bindings[] = $this->binding(sprintf('command-%03d', $index));
        }

        self::assertCount(256, (new CommandHandlerSet($bindings))->bindings());
    }

    /** @return iterable<string, array{array<array-key, mixed>}> */
    public static function invalidSets(): iterable
    {
        yield 'empty' => [[]];
        yield 'wrong member' => [[new \stdClass()]];
    }

    /** @param array<array-key, mixed> $bindings */
    #[DataProvider('invalidSets')]
    public function testInvalidSetsAreRejectedWithoutRetainingValues(array $bindings): void
    {
        $this->expectException(InvalidCommandRuntimeValueException::class);
        new CommandHandlerSet($bindings);
    }

    public function testDuplicateAndOverflowSetsAreRejected(): void
    {
        $duplicate = $this->binding('duplicate');
        foreach ([[$duplicate, $this->binding('duplicate')], array_fill(0, 257, $duplicate)] as $bindings) {
            try {
                new CommandHandlerSet($bindings);
                self::fail('Expected invalid handler set.');
            } catch (InvalidCommandRuntimeValueException $exception) {
                self::assertSame('KGN-CONSOLE-RUNTIME-1001', $exception->diagnosticId());
            }
        }
    }

    private function binding(string $identifier): CommandHandlerBinding
    {
        return new CommandHandlerBinding(new CommandIdentifier($identifier), new class implements CommandHandler {
            public function execute(CommandOperation $operation): CommandResult
            {
                return CommandResult::succeeded();
            }
        });
    }
}
