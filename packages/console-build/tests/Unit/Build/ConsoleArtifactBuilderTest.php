<?php

declare(strict_types=1);

namespace Kongono\ConsoleBuild\Tests\Unit\Build;

use Kongono\Console\CommandSpecification;
use Kongono\ConsoleBuild\Build\ConsoleArtifactBuilder;
use Kongono\ConsoleBuild\Internal\CanonicalJson;
use PHPUnit\Framework\TestCase;

final class ConsoleArtifactBuilderTest extends TestCase
{
    private const SOURCE = 'packages/console-build/tests/Fixture/Application/kongono.commands.json';

    public function testBuildIsDeterministicCanonicalAndBindsExactEvidence(): void
    {
        $root = dirname(__DIR__, 5);
        $builder = new ConsoleArtifactBuilder();
        $first = $builder->build($root, self::SOURCE);
        $second = $builder->build($root, self::SOURCE);

        self::assertSame($first->canonicalBytes(), $second->canonicalBytes());
        self::assertSame($first->artifactSha256(), $second->artifactSha256());
        self::assertSame($first->modelSha256(), $second->modelSha256());
        self::assertSame($first->sourceSha256(), $second->sourceSha256());
        self::assertSame($first->builderImplementationSha256(), $second->builderImplementationSha256());
        self::assertStringEndsWith("\n", $first->canonicalBytes());
        self::assertStringNotContainsString("\r", $first->canonicalBytes());
        self::assertSame(hash('sha256', $first->canonicalBytes()), $first->artifactSha256());

        $data = CanonicalJson::decodeObject($first->canonicalBytes(), 64);
        self::assertSame(['schema', 'version', 'model_schema', 'model_version', 'source', 'provenance', 'model'], array_keys($data));
        self::assertSame(['records:update', 'status'], array_map(
            static fn(CommandSpecification $command): string => $command->definition()->identifier()->value(),
            $first->model()->specifications()->commands(),
        ));
    }

    public function testReversedCommandAndInputArraysEmitIdenticalModelAndArtifactBytes(): void
    {
        $forward = $this->source();
        $reversed = $forward;
        $reversed['commands'] = array_reverse($forward['commands']);
        $firstCommand = $reversed['commands'][1];
        $firstCommand['inputs'] = array_reverse($firstCommand['inputs']);
        $reversed['commands'][1] = $firstCommand;

        $firstRoot = $this->temporaryRoot($forward);
        $secondRoot = $this->temporaryRoot($reversed);
        try {
            $builder = new ConsoleArtifactBuilder();
            $first = $builder->build($firstRoot, 'kongono.commands.json');
            $second = $builder->build($secondRoot, 'kongono.commands.json');

            self::assertSame($first->modelSha256(), $second->modelSha256());
            self::assertSame($first->sourceSha256(), $second->sourceSha256());
            self::assertSame($first->canonicalBytes(), $second->canonicalBytes());
            self::assertSame($first->artifactSha256(), $second->artifactSha256());
        } finally {
            $this->removeRoot($firstRoot);
            $this->removeRoot($secondRoot);
        }
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
    private function source(): array
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
                ['identifier' => 'zulu', 'summary' => 'Zulu command.', 'interaction' => 'declared', 'mutation' => 'mutation_capable', 'inputs' => []],
            ],
        ];
    }

    /** @param array<string, mixed> $source */
    private function temporaryRoot(array $source): string
    {
        $root = sys_get_temp_dir() . '/kongono-console-build-' . bin2hex(random_bytes(8));
        self::assertTrue(mkdir($root));
        $bytes = json_encode($source, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . "\n";
        self::assertSame(strlen($bytes), file_put_contents($root . '/kongono.commands.json', $bytes));

        return $root;
    }

    private function removeRoot(string $root): void
    {
        $source = $root . '/kongono.commands.json';
        if (is_file($source)) {
            unlink($source);
        }
        if (is_dir($root)) {
            rmdir($root);
        }
    }
}
