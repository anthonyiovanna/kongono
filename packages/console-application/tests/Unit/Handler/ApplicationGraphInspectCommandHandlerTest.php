<?php

declare(strict_types=1);

namespace Kongono\ConsoleApplication\Tests\Unit\Handler;

use Kongono\ApplicationGraphArtifact\Artifact\ApplicationGraphArtifact;
use Kongono\ApplicationGraphArtifact\Artifact\ApplicationGraphArtifactBuilder;
use Kongono\ApplicationGraphArtifact\Artifact\ApplicationGraphArtifactLoader;
use Kongono\ApplicationGraphArtifact\Artifact\ApplicationGraphArtifactPublisher;
use Kongono\ApplicationGraphBuild\Build\ApplicationGraphBuilder;
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
use Kongono\Console\CommandResultFieldDisclosure;
use Kongono\Console\CommandResultFieldName;
use Kongono\Console\CommandResultRecord;
use Kongono\Console\CommandSummary;
use Kongono\ConsoleApplication\Handler\ApplicationGraphInspectCommandHandler;
use Kongono\ConsoleApplication\KongoApplicationFactory;
use Kongono\ConsoleApplication\KongoProcessRequest;
use Kongono\ConsoleApplication\Process\NeverCancelled;
use Kongono\ConsolePresentation\CommandOutputPort;
use Kongono\ConsoleRuntime\CommandExecutionOperation;
use PHPUnit\Framework\TestCase;

final class ApplicationGraphInspectCommandHandlerTest extends TestCase
{
    private ?string $root = null;

    /** @var list<string> */
    private array $aliases = [];

    protected function tearDown(): void
    {
        foreach ($this->aliases as $alias) {
            is_link($alias) ? unlink($alias) : rmdir($alias);
        }
        if (null !== $this->root) {
            self::removeTree($this->root);
        }
    }

    public function testPublishedArtifactProducesExactSafeOrdinalFieldsAndPresentationParity(): void
    {
        [$artifact, $path] = $this->artifact();
        $before = self::treeIdentity($this->root ?? '');
        $handler = new ApplicationGraphInspectCommandHandler(new ApplicationGraphArtifactLoader());
        $result = $handler->execute(self::operation($path));
        $record = $result->data();
        self::assertSame('succeeded', $result->outcome()->value);
        self::assertInstanceOf(CommandResultRecord::class, $record);

        $expectedNames = self::fieldNames();
        self::assertSame($expectedNames, array_map(static fn($field): string => $field->name()->value(), $record->fields()));
        $expectedValues = [
            'artifact_sha256' => $artifact->artifactSha256(),
            'composition_consumes_input_count' => 0,
            'composition_contract_count' => 0,
            'composition_depends_on_count' => 0,
            'composition_exposes_root_count' => 0,
            'composition_implements_contract_count' => 0,
            'composition_input_count' => 0,
            'composition_root_count' => 0,
            'composition_service_count' => 0,
            'graph_contract_version' => 1,
            'graph_identity' => $artifact->graphIdentity()->value(),
            'node_count' => 0,
            'relation_count' => 0,
            'schema' => 'kongono.application-graph-inspection',
            'version' => 1,
        ];
        foreach ($expectedValues as $name => $value) {
            $field = $record->field(new CommandResultFieldName($name));
            self::assertNotNull($field);
            self::assertSame($value, $field->value()->value());
            self::assertSame(CommandResultFieldDisclosure::Safe, $field->disclosure());
        }

        [$machineStatus, $machineOut, $machineError] = self::execute(['--json', 'application-graph:inspect', '--artifact-path=' . $path, '--no-interaction']);
        [$humanStatus, $humanOut, $humanError] = self::execute(['application-graph:inspect', '--artifact-path=' . $path]);
        self::assertSame(0, $machineStatus);
        self::assertSame(0, $humanStatus);
        self::assertSame('', $machineError);
        self::assertSame('', $humanError);
        $machine = json_decode($machineOut, true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($machine);
        $data = $machine['data'] ?? null;
        if (!is_array($data)) {
            self::fail('Machine result data must be an object.');
        }
        $fields = $data['fields'] ?? null;
        if (!is_array($fields) || !array_is_list($fields)) {
            self::fail('Machine result fields must be a list.');
        }
        $machineNames = [];
        foreach ($fields as $field) {
            if (!is_array($field) || !is_string($field['name'] ?? null)) {
                self::fail('Every machine result field must have a string name.');
            }
            $machineNames[] = $field['name'];
        }
        self::assertSame($expectedNames, $machineNames);
        foreach ([$machineOut, $humanOut] as $document) {
            self::assertStringContainsString($artifact->artifactSha256(), $document);
            self::assertStringContainsString($artifact->graphIdentity()->value(), $document);
            self::assertStringNotContainsString($path, $document);
            self::assertStringNotContainsString('artifact-path', $document);
        }
        self::assertSame($before, self::treeIdentity($this->root ?? ''));
    }

    public function testHelpExposesOneReadOnlyOrdinaryInput(): void
    {
        [$status, $out, $error] = self::execute(['--json', 'application-graph:inspect', '--help']);
        self::assertSame(0, $status);
        self::assertSame('', $error);
        $help = json_decode($out, true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($help);
        self::assertSame('kongono.command-help', $help['schema']);
        self::assertSame(1, $help['version']);
        $commands = $help['commands'] ?? null;
        if (!is_array($commands) || !array_is_list($commands)) {
            self::fail('Machine command help commands must be a list.');
        }
        self::assertCount(1, $commands);
        $command = $commands[0] ?? null;
        if (!is_array($command)) {
            self::fail('Machine command help must contain one command object.');
        }
        if (
            !is_string($command['identifier'] ?? null)
            || !is_string($command['summary'] ?? null)
            || !is_string($command['mutation'] ?? null)
            || !is_string($command['interaction'] ?? null)
        ) {
            self::fail('Machine command help scalar fields must be strings.');
        }
        $inputs = $command['inputs'] ?? null;
        if (!is_array($inputs) || !array_is_list($inputs)) {
            self::fail('Machine command help inputs must be a list.');
        }
        foreach ($inputs as $input) {
            if (
                !is_array($input)
                || !is_string($input['name'] ?? null)
                || !is_string($input['requirement'] ?? null)
                || !is_string($input['sensitivity'] ?? null)
            ) {
                self::fail('Every machine command help input must have the exact scalar shape.');
            }
        }
        self::assertSame('application-graph:inspect', $command['identifier']);
        self::assertSame('Inspect one authoritative application graph artifact', $command['summary']);
        self::assertSame('read_only', $command['mutation']);
        self::assertSame('none', $command['interaction']);
        self::assertSame([['name' => 'artifact-path', 'requirement' => 'required', 'sensitivity' => 'ordinary']], $inputs);
    }

    public function testArtifactFailuresAreFixedPreconditionsWithoutPathOrContentDisclosure(): void
    {
        [$artifact, $path] = $this->artifact();
        $root = $this->root ?? '';
        $invalid = [$root . '/sentinel-missing.json'];
        $invalid[] = self::write($root . '/sentinel-malformed.json', "{sentinel-content}\n");
        $invalid[] = self::write($root . '/sentinel-noncanonical.json', str_replace('{"schema"', '{ "schema"', $artifact->canonicalBytes()));
        $invalid[] = self::write($root . '/sentinel-misnamed.json', $artifact->canonicalBytes());
        $invalid[] = self::write($root . '/sentinel-changed.json', $artifact->canonicalBytes() . "sentinel-content\n");
        $oversized = $root . '/sentinel-oversized.json';
        $handle = fopen($oversized, 'wb');
        self::assertIsResource($handle);
        self::assertSame(0, fseek($handle, ApplicationGraphArtifactBuilder::MAX_ARTIFACT_BYTES, SEEK_SET));
        self::assertSame(1, fwrite($handle, 'x'));
        self::assertTrue(fclose($handle));
        $invalid[] = $oversized;
        $linked = $this->linkedArtifact($root, $path);
        if (null !== $linked) {
            $invalid[] = $linked;
        }

        foreach ($invalid as $candidate) {
            [$status, $out, $error] = self::execute(['--json', 'application-graph:inspect', '--artifact-path=' . $candidate]);
            self::assertSame(3, $status);
            self::assertSame('', $error);
            self::assertStringContainsString('KGN-CONSOLE-APPLICATION-1004', $out);
            self::assertStringNotContainsString('sentinel', $out);
            self::assertStringNotContainsString($root, $out);
            self::assertStringNotContainsString('content', $out);
        }
    }

    public function testInvocationShapeFailuresAreInvalidInputAndSequentialCallsRetainNoState(): void
    {
        [, $path] = $this->artifact();
        $handler = new ApplicationGraphInspectCommandHandler(new ApplicationGraphArtifactLoader());
        $invalidOperations = [
            self::operation($path, identifier: 'observability:inspect'),
            self::operation($path, mutation: CommandMutationPolicy::MutationCapable, intent: CommandExecutionIntent::Plan),
            self::operation($path, interactionPolicy: CommandInteractionPolicy::Declared, interactionMode: CommandInteractionMode::Permitted),
            self::operation($path, inputs: []),
            self::operation($path, inputs: [new CommandInput(new CommandInputName('wrong-name'), new CommandInputValue($path), CommandInputSensitivity::Ordinary)]),
        ];
        foreach ($invalidOperations as $operation) {
            $result = $handler->execute($operation);
            self::assertSame('rejected', $result->outcome()->value);
            self::assertSame('invalid_input', $result->failure()?->category()->value);
        }

        $failure = $handler->execute(self::operation(($this->root ?? '') . '/sentinel-missing.json'));
        $success = $handler->execute(self::operation($path));
        $successAgain = $handler->execute(self::operation($path));
        $failureAgain = $handler->execute(self::operation(($this->root ?? '') . '/sentinel-missing.json'));
        self::assertSame('precondition', $failure->failure()?->category()->value);
        self::assertSame('succeeded', $success->outcome()->value);
        self::assertSame('succeeded', $successAgain->outcome()->value);
        self::assertSame('precondition', $failureAgain->failure()?->category()->value);
        self::assertEquals($success->data(), $successAgain->data());

        $source = (string) file_get_contents(dirname(__DIR__, 3) . '/src/Handler/ApplicationGraphInspectCommandHandler.php');
        self::assertSame(1, substr_count($source, '->load('));
        self::assertStringContainsString('catch (Throwable)', $source);
        self::assertStringContainsString('CommandFailureCategory::Infrastructure', $source);
    }

    public function testSourceAndComposerProxyExecuteIdenticallyInSeparateProcesses(): void
    {
        [, $path] = $this->artifact();
        $repository = dirname(__DIR__, 5);
        $source = self::process([PHP_BINARY, $repository . '/packages/console-application/bin/kongo', '--json', 'application-graph:inspect', '--artifact-path=' . $path]);
        $proxy = self::process([PHP_BINARY, $repository . '/vendor/bin/kongo', '--json', 'application-graph:inspect', '--artifact-path=' . $path]);
        self::assertSame(0, $source['exit']);
        self::assertSame('', $source['stderr']);
        self::assertSame($source, $proxy);
    }

    /** @return array{ApplicationGraphArtifact, string} */
    private function artifact(): array
    {
        $this->root = str_replace('\\', '/', sys_get_temp_dir()) . '/kongono-graph-inspection-command-' . bin2hex(random_bytes(12));
        self::assertTrue(mkdir($this->root, 0700, true));
        $artifact = (new ApplicationGraphArtifactBuilder())->build((new ApplicationGraphBuilder())->build([], []));
        $path = (new ApplicationGraphArtifactPublisher())->publish($artifact, $this->root);

        return [$artifact, $path];
    }

    /**
     * @param list<CommandInput>|null $inputs
     */
    private static function operation(
        string $path,
        string $identifier = 'application-graph:inspect',
        CommandMutationPolicy $mutation = CommandMutationPolicy::ReadOnly,
        CommandExecutionIntent $intent = CommandExecutionIntent::Observe,
        CommandInteractionPolicy $interactionPolicy = CommandInteractionPolicy::None,
        CommandInteractionMode $interactionMode = CommandInteractionMode::Disabled,
        ?array $inputs = null,
    ): CommandExecutionOperation {
        $definition = new CommandDefinition(new CommandIdentifier($identifier), new CommandSummary('Inspect one authoritative application graph artifact'), $interactionPolicy, $mutation);
        $inputs ??= [new CommandInput(new CommandInputName('artifact-path'), new CommandInputValue($path), CommandInputSensitivity::Ordinary)];
        $invocation = new CommandInvocation($definition, $inputs, $intent, $interactionMode);

        return new CommandExecutionOperation($invocation, new NeverCancelled());
    }

    /**
     * @param list<string> $arguments
     * @return array{int, string, string}
     */
    private static function execute(array $arguments): array
    {
        $stdout = self::port();
        $stderr = self::port();
        $status = (new KongoApplicationFactory())->create()->run(new KongoProcessRequest($arguments), $stdout, $stderr);

        return [$status, $stdout->bytes, $stderr->bytes];
    }

    /** @return CommandOutputPort&object{bytes: string} */
    private static function port(): CommandOutputPort
    {
        return new class implements CommandOutputPort {
            public string $bytes = '';

            public function write(string $bytes): void
            {
                if ('' !== $this->bytes) {
                    throw new \RuntimeException('duplicate write');
                }
                $this->bytes = $bytes;
            }
        };
    }

    /** @return list<string> */
    private static function fieldNames(): array
    {
        return [
            'artifact_sha256',
            'composition_consumes_input_count',
            'composition_contract_count',
            'composition_depends_on_count',
            'composition_exposes_root_count',
            'composition_implements_contract_count',
            'composition_input_count',
            'composition_root_count',
            'composition_service_count',
            'graph_contract_version',
            'graph_identity',
            'node_count',
            'relation_count',
            'schema',
            'version',
        ];
    }

    private function linkedArtifact(string $root, string $path): ?string
    {
        $alias = $root . '-alias';
        if ('\\' === DIRECTORY_SEPARATOR) {
            $result = self::process(['cmd', '/c', 'mklink', '/J', str_replace('/', '\\', $alias), str_replace('/', '\\', $root)]);
            if (0 !== $result['exit']) {
                return null;
            }
        } elseif (!symlink($root, $alias)) {
            return null;
        }
        $this->aliases[] = $alias;

        return $alias . '/' . basename($path);
    }

    private static function write(string $path, string $bytes): string
    {
        self::assertSame(strlen($bytes), file_put_contents($path, $bytes));

        return $path;
    }

    private static function treeIdentity(string $directory): string
    {
        $entries = [];
        foreach (new \FilesystemIterator($directory, \FilesystemIterator::SKIP_DOTS) as $entry) {
            if (!$entry instanceof \SplFileInfo) {
                self::fail('Filesystem iterator entries must be file information objects.');
            }
            $entries[] = $entry->getFilename() . ':' . $entry->getSize() . ':' . hash_file('sha256', $entry->getPathname());
        }
        sort($entries, SORT_STRING);

        return hash('sha256', implode("\n", $entries));
    }

    /**
     * @param list<string> $command
     * @return array{exit: int, stdout: string, stderr: string}
     */
    private static function process(array $command): array
    {
        $pipes = [];
        $process = proc_open($command, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        self::assertIsResource($process);
        self::assertTrue(fclose($pipes[0]));
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        self::assertIsString($stdout);
        self::assertIsString($stderr);
        self::assertTrue(fclose($pipes[1]));
        self::assertTrue(fclose($pipes[2]));

        return ['exit' => proc_close($process), 'stdout' => $stdout, 'stderr' => $stderr];
    }

    private static function removeTree(string $root): void
    {
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($iterator as $item) {
            if ($item instanceof \SplFileInfo) {
                $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
            }
        }
        rmdir($root);
    }
}
