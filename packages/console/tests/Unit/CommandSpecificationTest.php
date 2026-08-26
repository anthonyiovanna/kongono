<?php

declare(strict_types=1);

namespace Kongono\Console\Tests\Unit;

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
use Kongono\Console\Exception\InvalidConsoleValueException;
use PHPUnit\Framework\TestCase;

final class CommandSpecificationTest extends TestCase
{
    public function testSpecificationsAreDetachedSortedAndExactlyAddressable(): void
    {
        $alpha = $this->input('alpha');
        $zulu = $this->input('zulu');
        $source = [9 => $zulu, 2 => $alpha];
        $specification = new CommandSpecification($this->definition('z-command'), $source);
        $source[] = $this->input('later');

        self::assertSame([$alpha, $zulu], $specification->inputs());
        self::assertSame($alpha, $specification->input(new CommandInputName('alpha')));
        self::assertTrue($specification->hasInput(new CommandInputName('zulu')));
        self::assertNull($specification->input(new CommandInputName('missing')));

        $first = new CommandSpecification($this->definition('a-command'), []);
        $setSource = [8 => $specification, 3 => $first];
        $set = new CommandSpecificationSet($setSource);
        $setSource[] = new CommandSpecification($this->definition('later'), []);

        self::assertSame([$first, $specification], $set->commands());
        self::assertSame($first, $set->command(new CommandIdentifier('a-command')));
        self::assertTrue($set->hasCommand(new CommandIdentifier('z-command')));
        self::assertFalse($set->hasCommand(new CommandIdentifier('missing')));
    }

    public function testSpecificationBoundsTypesAndUniquenessFailClosed(): void
    {
        $definition = $this->definition('test');
        $invalid = [
            static fn(): CommandSpecification => new CommandSpecification($definition, [new \stdClass()]),
            fn(): CommandSpecification => new CommandSpecification($definition, [$this->input('same'), $this->input('same')]),
            fn(): CommandSpecification => new CommandSpecification($definition, array_map(fn(int $index): CommandInputDefinition => $this->input('input-' . $index), range(0, 64))),
            static fn(): CommandSpecificationSet => new CommandSpecificationSet([]),
            static fn(): CommandSpecificationSet => new CommandSpecificationSet([new \stdClass()]),
            fn(): CommandSpecificationSet => new CommandSpecificationSet([new CommandSpecification($definition, []), new CommandSpecification($definition, [])]),
            fn(): CommandSpecificationSet => new CommandSpecificationSet(array_map(fn(int $index): CommandSpecification => new CommandSpecification($this->definition('command-' . $index), []), range(0, 256))),
        ];

        foreach ($invalid as $operation) {
            try {
                $operation();
                self::fail('Expected invalid specification evidence to be rejected.');
            } catch (InvalidConsoleValueException $exception) {
                self::assertSame('KGN-CONSOLE-1002', $exception->diagnosticId());
            }
        }
    }

    private function definition(string $identifier): CommandDefinition
    {
        return new CommandDefinition(new CommandIdentifier($identifier), new CommandSummary('Test command.'), CommandInteractionPolicy::None, CommandMutationPolicy::ReadOnly);
    }

    private function input(string $name): CommandInputDefinition
    {
        return new CommandInputDefinition(new CommandInputName($name), CommandInputRequirement::Optional, CommandInputSensitivity::Ordinary);
    }
}
