<?php

declare(strict_types=1);

namespace Kongono\ConsoleCommands\Tests\Unit;

use Kongono\ConsoleBuild\Build\ConsoleArtifactBuilder;
use Kongono\ConsoleBuild\Build\ConsoleArtifactValidator;
use Kongono\ConsoleCommands\ReadOnlyFrameworkCommand;
use PHPUnit\Framework\TestCase;

final class ReadOnlyFrameworkCommandTest extends TestCase
{
    public function testIdentifiersOrderAndExactSourceModelAreCanonical(): void
    {
        self::assertSame(
            ['framework:composition', 'framework:graph', 'framework:quality'],
            array_map(static fn(ReadOnlyFrameworkCommand $command): string => $command->value, ReadOnlyFrameworkCommand::cases()),
        );

        $root = dirname(__DIR__, 2);
        $expected = '{"schema":"kongono.command-source","version":1,"commands":[{"identifier":"framework:composition","summary":"Inspect supplied composition artifact identities","interaction":"none","mutation":"read_only","inputs":[]},{"identifier":"framework:graph","summary":"Inspect supplied application graph summary","interaction":"none","mutation":"read_only","inputs":[]},{"identifier":"framework:quality","summary":"Inspect supplied Hantei quality verdict","interaction":"none","mutation":"read_only","inputs":[]}]}' . "\n";
        self::assertSame($expected, file_get_contents($root . '/resources/kongono.commands.json'));

        $artifact = (new ConsoleArtifactBuilder())->build($root, 'resources/kongono.commands.json');
        $validated = (new ConsoleArtifactValidator())->validateCurrent(
            $root,
            'resources/kongono.commands.json',
            $artifact->canonicalBytes(),
        );
        $facts = [];
        foreach ($validated->model()->specifications()->commands() as $command) {
            $definition = $command->definition();
            $facts[] = [
                $definition->identifier()->value(),
                $definition->summary()->value(),
                $definition->interactionPolicy()->value,
                $definition->mutationPolicy()->value,
                count($command->inputs()),
            ];
        }
        self::assertSame([
            ['framework:composition', 'Inspect supplied composition artifact identities', 'none', 'read_only', 0],
            ['framework:graph', 'Inspect supplied application graph summary', 'none', 'read_only', 0],
            ['framework:quality', 'Inspect supplied Hantei quality verdict', 'none', 'read_only', 0],
        ], $facts);
    }
}
