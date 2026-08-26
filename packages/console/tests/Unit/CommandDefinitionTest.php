<?php

declare(strict_types=1);

namespace Kongono\Console\Tests\Unit;

use Kongono\Console\CommandDefinition;
use Kongono\Console\CommandIdentifier;
use Kongono\Console\CommandInteractionPolicy;
use Kongono\Console\CommandMutationPolicy;
use Kongono\Console\CommandSummary;
use Kongono\Console\Exception\InvalidConsoleValueException;
use PHPUnit\Framework\TestCase;

final class CommandDefinitionTest extends TestCase
{
    public function testSummaryBoundsAndDefinitionAccessorsAreExact(): void
    {
        $identifier = new CommandIdentifier('graph:inspect');
        $summary = new CommandSummary('Inspect the current application graph.');
        $definition = new CommandDefinition(
            $identifier,
            $summary,
            CommandInteractionPolicy::None,
            CommandMutationPolicy::ReadOnly,
        );

        self::assertSame($identifier, $definition->identifier());
        self::assertSame($summary, $definition->summary());
        self::assertSame(CommandInteractionPolicy::None, $definition->interactionPolicy());
        self::assertSame(CommandMutationPolicy::ReadOnly, $definition->mutationPolicy());
        self::assertSame(str_repeat('x', 160), (new CommandSummary(str_repeat('x', 160)))->value());
        self::assertSame(['none', 'declared'], array_column(CommandInteractionPolicy::cases(), 'value'));
        self::assertSame(['read_only', 'mutation_capable'], array_column(CommandMutationPolicy::cases(), 'value'));
    }

    public function testSummaryRejectsEmptyUntrimmedInvalidControlAndOversizedValues(): void
    {
        foreach (['', ' leading', 'trailing ', "line\nbreak", "tab\tvalue", "control\u{0085}", "\xC3\x28", str_repeat('x', 161)] as $value) {
            try {
                new CommandSummary($value);
                self::fail('Expected an invalid summary to be rejected.');
            } catch (InvalidConsoleValueException $exception) {
                self::assertSame('KGN-CONSOLE-1001', $exception->diagnosticId());
                if ('' !== $value) {
                    self::assertStringNotContainsString($value, $exception->getMessage());
                    self::assertStringNotContainsString($value, (string) $exception);
                }
            }
        }
    }
}
