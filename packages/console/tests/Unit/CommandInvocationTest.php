<?php

declare(strict_types=1);

namespace Kongono\Console\Tests\Unit;

use Kongono\Console\CommandDefinition;
use Kongono\Console\CommandExecutionIntent;
use Kongono\Console\CommandIdentifier;
use Kongono\Console\CommandInput;
use Kongono\Console\CommandInputName;
use Kongono\Console\CommandInputSensitivity;
use Kongono\Console\CommandInputValue;
use Kongono\Console\CommandInteractionMode;
use Kongono\Console\CommandInteractionPolicy;
use Kongono\Console\CommandInvocation;
use Kongono\Console\CommandMutationPolicy;
use Kongono\Console\CommandSummary;
use Kongono\Console\Exception\InvalidConsoleValueException;
use PHPUnit\Framework\TestCase;

final class CommandInvocationTest extends TestCase
{
    public function testInputsAreDetachedReindexedOrderedAndLookupIsExact(): void
    {
        $first = $this->input('first', 'one');
        $second = $this->input('second', 'two');
        $source = [9 => $first, 4 => $second];
        $invocation = new CommandInvocation(
            $this->definition(CommandInteractionPolicy::Declared, CommandMutationPolicy::ReadOnly),
            $source,
            CommandExecutionIntent::Observe,
            CommandInteractionMode::Permitted,
        );
        $source[] = $this->input('third', 'three');

        self::assertSame([$first, $second], $invocation->inputs());
        self::assertSame($first, $invocation->input(new CommandInputName('first')));
        self::assertTrue($invocation->hasInput(new CommandInputName('second')));
        self::assertNull($invocation->input(new CommandInputName('missing')));
        self::assertFalse($invocation->hasInput(new CommandInputName('missing')));
        self::assertSame(CommandExecutionIntent::Observe, $invocation->executionIntent());
        self::assertSame(CommandInteractionMode::Permitted, $invocation->interactionMode());
    }

    public function testBoundsTypesDuplicatesAndEveryPolicyCombinationAreValidated(): void
    {
        $readOnlyNone = $this->definition(CommandInteractionPolicy::None, CommandMutationPolicy::ReadOnly);
        $mutationDeclared = $this->definition(CommandInteractionPolicy::Declared, CommandMutationPolicy::MutationCapable);

        self::assertSame(CommandExecutionIntent::Observe, (new CommandInvocation($readOnlyNone, [], CommandExecutionIntent::Observe, CommandInteractionMode::Disabled))->executionIntent());
        self::assertSame(CommandExecutionIntent::Plan, (new CommandInvocation($mutationDeclared, [], CommandExecutionIntent::Plan, CommandInteractionMode::Disabled))->executionIntent());
        self::assertSame(CommandExecutionIntent::Apply, (new CommandInvocation($mutationDeclared, [], CommandExecutionIntent::Apply, CommandInteractionMode::Permitted))->executionIntent());

        $invalid = [
            static fn(): CommandInvocation => new CommandInvocation($readOnlyNone, [], CommandExecutionIntent::Plan, CommandInteractionMode::Disabled),
            static fn(): CommandInvocation => new CommandInvocation($readOnlyNone, [], CommandExecutionIntent::Apply, CommandInteractionMode::Disabled),
            static fn(): CommandInvocation => new CommandInvocation($mutationDeclared, [], CommandExecutionIntent::Observe, CommandInteractionMode::Disabled),
            static fn(): CommandInvocation => new CommandInvocation($readOnlyNone, [], CommandExecutionIntent::Observe, CommandInteractionMode::Permitted),
            fn(): CommandInvocation => new CommandInvocation($readOnlyNone, [new \stdClass()], CommandExecutionIntent::Observe, CommandInteractionMode::Disabled),
            fn(): CommandInvocation => new CommandInvocation($readOnlyNone, [$this->input('same', 'one'), $this->input('same', 'two')], CommandExecutionIntent::Observe, CommandInteractionMode::Disabled),
            fn(): CommandInvocation => new CommandInvocation($readOnlyNone, array_map(fn(int $index): CommandInput => $this->input('input-' . $index, 'value'), range(0, 64)), CommandExecutionIntent::Observe, CommandInteractionMode::Disabled),
        ];

        foreach ($invalid as $operation) {
            try {
                $operation();
                self::fail('Expected an invalid invocation to be rejected.');
            } catch (InvalidConsoleValueException $exception) {
                self::assertSame('KGN-CONSOLE-1003', $exception->diagnosticId());
            }
        }
    }

    private function definition(CommandInteractionPolicy $interaction, CommandMutationPolicy $mutation): CommandDefinition
    {
        return new CommandDefinition(new CommandIdentifier('test'), new CommandSummary('Test command.'), $interaction, $mutation);
    }

    private function input(string $name, string $value): CommandInput
    {
        return new CommandInput(new CommandInputName($name), new CommandInputValue($value), CommandInputSensitivity::Ordinary);
    }
}
