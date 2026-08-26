<?php

declare(strict_types=1);

namespace Kongono\ConsoleCommands\Tests\Unit;

use Kongono\ApplicationGraph\Model\ApplicationGraph;
use Kongono\Composition\Build\CompositionArtifact;
use Kongono\Console\CommandDefinition;
use Kongono\Console\CommandIdentifier;
use Kongono\Console\CommandInputDefinition;
use Kongono\Console\CommandInputName;
use Kongono\Console\CommandInputRequirement;
use Kongono\Console\CommandInputSensitivity;
use Kongono\Console\CommandInteractionPolicy;
use Kongono\Console\CommandMutationPolicy;
use Kongono\Console\CommandSpecification;
use Kongono\Console\CommandSpecificationSet;
use Kongono\Console\CommandSummary;
use Kongono\ConsoleCommands\Exception\InvalidConsoleCommandsValueException;
use Kongono\ConsoleCommands\Handler\CompositionInspectCommandHandler;
use Kongono\ConsoleCommands\Handler\GraphInspectCommandHandler;
use Kongono\ConsoleCommands\Handler\QualityInspectCommandHandler;
use Kongono\ConsoleCommands\ReadOnlyFrameworkCommandFamily;
use Kongono\Hantei\HanteiCheckEvidence;
use Kongono\Hantei\HanteiCheckOutcome;
use Kongono\Hantei\HanteiReport;
use Kongono\Hantei\HanteiVerdict;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class ReadOnlyFrameworkCommandFamilyTest extends TestCase
{
    public function testFamilyRetainsExactSpecificationsAndBindings(): void
    {
        $specifications = self::specifications();
        $family = self::family($specifications);

        self::assertSame($specifications, $family->specifications());
        self::assertSame(
            ['framework:composition', 'framework:graph', 'framework:quality'],
            array_map(static fn($binding): string => $binding->identifier()->value(), $family->handlers()->bindings()),
        );
        self::assertInstanceOf(CompositionInspectCommandHandler::class, $family->handlers()->bindings()[0]->handler());
        self::assertInstanceOf(GraphInspectCommandHandler::class, $family->handlers()->bindings()[1]->handler());
        self::assertInstanceOf(QualityInspectCommandHandler::class, $family->handlers()->bindings()[2]->handler());
    }

    /** @param callable(): CommandSpecificationSet $invalid */
    #[DataProvider('invalidSets')]
    public function testFamilyRejectsNonExactSetsBeforeConstruction(callable $invalid): void
    {
        $this->expectException(InvalidConsoleCommandsValueException::class);
        self::family($invalid());
    }

    /** @return iterable<string, array{callable(): CommandSpecificationSet}> */
    public static function invalidSets(): iterable
    {
        yield 'missing' => [static fn(): CommandSpecificationSet => new CommandSpecificationSet(array_slice(self::commands(), 0, 2))];
        yield 'extra' => [static fn(): CommandSpecificationSet => new CommandSpecificationSet([...self::commands(), self::command('framework:extra', 'Extra', CommandInteractionPolicy::None, CommandMutationPolicy::ReadOnly)])];
        yield 'stale' => [static fn(): CommandSpecificationSet => new CommandSpecificationSet([self::command('framework:composition', 'Stale', CommandInteractionPolicy::None, CommandMutationPolicy::ReadOnly), ...array_slice(self::commands(), 1)])];
        yield 'mutating' => [static fn(): CommandSpecificationSet => new CommandSpecificationSet([self::command('framework:composition', 'Inspect supplied composition artifact identities', CommandInteractionPolicy::None, CommandMutationPolicy::MutationCapable), ...array_slice(self::commands(), 1)])];
        yield 'interactive' => [static fn(): CommandSpecificationSet => new CommandSpecificationSet([self::command('framework:composition', 'Inspect supplied composition artifact identities', CommandInteractionPolicy::Declared, CommandMutationPolicy::ReadOnly), ...array_slice(self::commands(), 1)])];
        yield 'input-bearing' => [static fn(): CommandSpecificationSet => new CommandSpecificationSet([
            new CommandSpecification(
                self::command('framework:composition', 'Inspect supplied composition artifact identities', CommandInteractionPolicy::None, CommandMutationPolicy::ReadOnly)->definition(),
                [new CommandInputDefinition(new CommandInputName('sentinel'), CommandInputRequirement::Optional, CommandInputSensitivity::Ordinary)],
            ),
            ...array_slice(self::commands(), 1),
        ])];
        yield 'reordered invariant bypass' => [static function (): CommandSpecificationSet {
            $reflection = new ReflectionClass(CommandSpecificationSet::class);
            $set = $reflection->newInstanceWithoutConstructor();
            $reflection->getProperty('commands')->setValue($set, array_reverse(self::commands()));

            return $set;
        }];
    }

    private static function family(CommandSpecificationSet $specifications): ReadOnlyFrameworkCommandFamily
    {
        return new ReadOnlyFrameworkCommandFamily(
            $specifications,
            new CompositionInspectCommandHandler(self::artifact()),
            new GraphInspectCommandHandler(new ApplicationGraph([], [])),
            new QualityInspectCommandHandler(self::report()),
        );
    }

    private static function specifications(): CommandSpecificationSet
    {
        return new CommandSpecificationSet(self::commands());
    }

    /** @return list<CommandSpecification> */
    private static function commands(): array
    {
        return [
            self::command('framework:composition', 'Inspect supplied composition artifact identities', CommandInteractionPolicy::None, CommandMutationPolicy::ReadOnly),
            self::command('framework:graph', 'Inspect supplied application graph summary', CommandInteractionPolicy::None, CommandMutationPolicy::ReadOnly),
            self::command('framework:quality', 'Inspect supplied Hantei quality verdict', CommandInteractionPolicy::None, CommandMutationPolicy::ReadOnly),
        ];
    }

    private static function command(string $identifier, string $summary, CommandInteractionPolicy $interaction, CommandMutationPolicy $mutation): CommandSpecification
    {
        return new CommandSpecification(
            new CommandDefinition(new CommandIdentifier($identifier), new CommandSummary($summary), $interaction, $mutation),
            [],
        );
    }

    private static function artifact(): CompositionArtifact
    {
        return new CompositionArtifact('protected-artifact-bytes', str_repeat('a', 64), str_repeat('b', 64), str_repeat('c', 64), str_repeat('d', 64), str_repeat('e', 64));
    }

    private static function report(): HanteiReport
    {
        $checks = [];
        foreach (HanteiCheckEvidence::CHECKS as $check => $script) {
            $checks[] = new HanteiCheckEvidence($check, $script, HanteiCheckOutcome::Pass, 0, 0, str_repeat('a', 64), 0, str_repeat('b', 64), null);
        }

        return new HanteiReport(HanteiVerdict::Pass, str_repeat('c', 64), str_repeat('d', 64), str_repeat('e', 64), str_repeat('f', 64), '8.5.9', str_repeat('1', 64), 'git version', $checks, []);
    }
}
