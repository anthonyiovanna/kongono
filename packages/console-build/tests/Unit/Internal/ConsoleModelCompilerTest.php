<?php

declare(strict_types=1);

namespace Kongono\ConsoleBuild\Tests\Unit\Internal;

use Kongono\ConsoleBuild\Exception\InvalidConsoleSourceException;
use Kongono\ConsoleBuild\Internal\ConsoleModelCompiler;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ConsoleModelCompilerTest extends TestCase
{
    public function testSourceOrderIsNonsemanticAndModelOrderIsOrdinal(): void
    {
        $compiler = new ConsoleModelCompiler();
        $forward = self::source();
        $reversed = $forward;
        $reversed['commands'] = array_reverse($forward['commands']);
        self::assertIsArray($reversed['commands']);
        $first = $reversed['commands'][1];
        $first['inputs'] = array_reverse($first['inputs']);
        $reversed['commands'][1] = $first;

        $firstData = $compiler->modelData($compiler->compile($forward));
        $secondData = $compiler->modelData($compiler->compile($reversed));
        self::assertSame($firstData, $secondData);
        $commands = $firstData['commands'];
        self::assertIsArray($commands);
        $firstCommand = $commands[0] ?? null;
        self::assertIsArray($firstCommand);
        $inputs = $firstCommand['inputs'];
        self::assertIsArray($inputs);
        self::assertSame(['alpha', 'zulu'], array_column($inputs, 'name'));
        self::assertSame(['alpha', 'zulu'], array_column($commands, 'identifier'));
        self::assertSame($firstData, $compiler->modelData($compiler->hydrateModel($firstData)));
    }

    /** @return iterable<string, array{array<string, mixed>}> */
    public static function invalidSources(): iterable
    {
        $source = self::source();
        $source['extra'] = true;
        yield 'unknown root key' => [$source];

        $source = self::source();
        unset($source['commands']);
        yield 'missing commands' => [$source];

        $source = self::source();
        $source['schema'] = 'wrong';
        yield 'wrong schema' => [$source];

        $source = self::source();
        $source['commands'] = 'wrong';
        yield 'scalar commands' => [$source];

        $source = self::source();
        $source['commands'] = [];
        yield 'empty commands' => [$source];

        $source = self::source();
        $source['commands'][0]['handler'] = 'forbidden';
        yield 'unknown command key' => [$source];

        $source = self::source();
        $source['commands'][0]['interaction'] = 'prompt';
        yield 'invalid interaction' => [$source];

        $source = self::source();
        $source['commands'][0]['mutation'] = 'apply';
        yield 'invalid mutation' => [$source];

        $source = self::source();
        $source['commands'][0]['inputs'][0]['required'] = 1;
        yield 'invalid required scalar' => [$source];

        $source = self::source();
        $source['commands'][0]['inputs'][0]['sensitivity'] = 'secret';
        yield 'invalid sensitivity' => [$source];

        $source = self::source();
        $source['commands'][] = $source['commands'][0];
        yield 'duplicate command' => [$source];

        $source = self::source();
        $source['commands'][0]['inputs'][] = $source['commands'][0]['inputs'][0];
        yield 'duplicate input' => [$source];
    }

    /** @param array<string, mixed> $source */
    #[DataProvider('invalidSources')]
    public function testInvalidSchemaScalarsEnumsAndIdentitiesFailClosed(array $source): void
    {
        $this->expectException(InvalidConsoleSourceException::class);
        (new ConsoleModelCompiler())->compile($source);
    }

    /**
     * @return array{
     *     schema: string,
     *     version: int,
     *     commands: list<array{
     *         identifier: string,
     *         summary: string,
     *         interaction: string,
     *         mutation: string,
     *         inputs: list<array{name: string, required: bool, sensitivity: string}>
     *     }>
     * }
     */
    private static function source(): array
    {
        return [
            'schema' => 'kongono.command-source',
            'version' => 1,
            'commands' => [
                [
                    'identifier' => 'alpha',
                    'summary' => 'Alpha command.',
                    'interaction' => 'none',
                    'mutation' => 'read_only',
                    'inputs' => [
                        ['name' => 'zulu', 'required' => false, 'sensitivity' => 'ordinary'],
                        ['name' => 'alpha', 'required' => true, 'sensitivity' => 'sensitive'],
                    ],
                ],
                [
                    'identifier' => 'zulu',
                    'summary' => 'Zulu command.',
                    'interaction' => 'declared',
                    'mutation' => 'mutation_capable',
                    'inputs' => [],
                ],
            ],
        ];
    }
}
