<?php

declare(strict_types=1);

namespace Kongono\ConsoleBuild\Tests\Unit\Build;

use Kongono\ConsoleBuild\Build\ConsoleArtifactBuilder;
use Kongono\ConsoleBuild\Build\ConsoleArtifactValidator;
use Kongono\ConsoleBuild\Exception\InvalidConsoleArtifactException;
use Kongono\ConsoleBuild\Exception\StaleConsoleArtifactException;
use Kongono\ConsoleBuild\Internal\CanonicalJson;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ConsoleArtifactValidatorTest extends TestCase
{
    private const SOURCE = 'packages/console-build/tests/Fixture/Application/kongono.commands.json';

    public function testStructuralAndCurrentValidationRehydrateExactEvidence(): void
    {
        $root = dirname(__DIR__, 5);
        $bytes = (new ConsoleArtifactBuilder())->build($root, self::SOURCE)->canonicalBytes();
        $validator = new ConsoleArtifactValidator();

        $structural = $validator->validateStructure($bytes);
        self::assertSame($bytes, $structural->canonicalBytes());
        self::assertSame(['records:update', 'status'], array_map(
            static fn($command): string => $command->definition()->identifier()->value(),
            $structural->model()->specifications()->commands(),
        ));
        self::assertSame($bytes, $validator->validateCurrent($root, self::SOURCE, $bytes)->canonicalBytes());
    }

    public function testSemanticSourceChangeMakesPreviouslyStructuralEvidenceStale(): void
    {
        $source = [
            'schema' => 'kongono.command-source',
            'version' => 1,
            'commands' => [['identifier' => 'status', 'summary' => 'Original status.', 'interaction' => 'none', 'mutation' => 'read_only', 'inputs' => []]],
        ];
        $root = $this->temporaryRoot($source);
        try {
            $builder = new ConsoleArtifactBuilder();
            $bytes = $builder->build($root, 'kongono.commands.json')->canonicalBytes();
            $changed = $source;
            $changed['commands'][0]['summary'] = 'Changed status.';
            $this->writeSource($root, $changed);

            $validator = new ConsoleArtifactValidator();
            self::assertSame($bytes, $validator->validateStructure($bytes)->canonicalBytes());
            $this->expectException(StaleConsoleArtifactException::class);
            $validator->validateCurrent($root, 'kongono.commands.json', $bytes);
        } finally {
            $this->removeRoot($root);
        }
    }

    /** @return iterable<string, array{string}> */
    public static function invalidArtifacts(): iterable
    {
        yield 'empty' => [''];
        yield 'malformed' => ['{'];
        yield 'duplicate key' => ["{\"schema\":1,\"schema\":1}\n"];
        yield 'noncanonical' => ["{ \"schema\": \"kongono.command-artifact\" }\n"];
        yield 'crlf' => ["{}\r\n"];
        yield 'oversized' => ["{\"value\":\"" . str_repeat('x', ConsoleArtifactBuilder::MAX_ARTIFACT_BYTES) . "\"}\n"];
    }

    #[DataProvider('invalidArtifacts')]
    public function testMalformedNoncanonicalAndOversizedArtifactsFailClosed(string $bytes): void
    {
        $this->expectException(InvalidConsoleArtifactException::class);
        (new ConsoleArtifactValidator())->validateStructure($bytes);
    }

    /** @return iterable<string, array{string}> */
    public static function tamperCases(): iterable
    {
        yield 'changed model' => ['model'];
        yield 'changed model digest' => ['model_digest'];
        yield 'changed source digest' => ['source_digest'];
        yield 'changed implementation digest' => ['implementation_digest'];
        yield 'unknown field' => ['unknown_field'];
        yield 'root order' => ['root_order'];
    }

    #[DataProvider('tamperCases')]
    public function testInternallyInconsistentOrChangedEvidenceFailsStructuralValidation(string $case): void
    {
        $root = dirname(__DIR__, 5);
        $data = CanonicalJson::decodeObject((new ConsoleArtifactBuilder())->build($root, self::SOURCE)->canonicalBytes(), 64);
        if ('model' === $case) {
            $model = $data['model'];
            self::assertIsArray($model);
            $commands = $model['commands'];
            self::assertIsArray($commands);
            $command = $commands[0] ?? null;
            self::assertIsArray($command);
            $command['summary'] = 'Attacker changed model.';
            $commands[0] = $command;
            $model['commands'] = $commands;
            $data['model'] = $model;
        } elseif ('model_digest' === $case) {
            $provenance = $data['provenance'];
            self::assertIsArray($provenance);
            $commandModel = $provenance['command_model'];
            self::assertIsArray($commandModel);
            $commandModel['sha256'] = str_repeat('0', 64);
            $provenance['command_model'] = $commandModel;
            $data['provenance'] = $provenance;
        } elseif ('source_digest' === $case) {
            $source = $data['source'];
            self::assertIsArray($source);
            $source['sha256'] = str_repeat('0', 64);
            $data['source'] = $source;
            $provenance = $data['provenance'];
            self::assertIsArray($provenance);
            $commandSource = $provenance['command_source'];
            self::assertIsArray($commandSource);
            $commandSource['sha256'] = str_repeat('0', 64);
            $provenance['command_source'] = $commandSource;
            $data['provenance'] = $provenance;
        } elseif ('implementation_digest' === $case) {
            $provenance = $data['provenance'];
            self::assertIsArray($provenance);
            $consoleBuilder = $provenance['console_builder'];
            self::assertIsArray($consoleBuilder);
            $consoleBuilder['implementation_sha256'] = str_repeat('0', 64);
            $provenance['console_builder'] = $consoleBuilder;
            $data['provenance'] = $provenance;
        } elseif ('unknown_field' === $case) {
            $data['unknown'] = true;
        } else {
            $data = ['version' => $data['version'], 'schema' => $data['schema']] + array_diff_key($data, ['version' => true, 'schema' => true]);
        }

        $this->expectException(InvalidConsoleArtifactException::class);
        (new ConsoleArtifactValidator())->validateStructure(CanonicalJson::encode($data));
    }

    /** @param array<string, mixed> $source */
    private function temporaryRoot(array $source): string
    {
        $root = sys_get_temp_dir() . '/kongono-console-current-' . bin2hex(random_bytes(8));
        self::assertTrue(mkdir($root));
        $this->writeSource($root, $source);

        return $root;
    }

    /** @param array<string, mixed> $source */
    private function writeSource(string $root, array $source): void
    {
        $bytes = json_encode($source, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . "\n";
        self::assertSame(strlen($bytes), file_put_contents($root . '/kongono.commands.json', $bytes));
        clearstatcache(true, $root . '/kongono.commands.json');
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
