<?php

declare(strict_types=1);

namespace Kongono\Tests\Integration\Phase8F;

use FilesystemIterator;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;

final class ReadOnlyFrameworkCommandCompositionTest extends TestCase
{
    public function testExactGeneratedFamilyDispatchAndPresentationPassExternally(): void
    {
        $repository = dirname(__DIR__, 3);
        $applicationRoot = self::temporaryDirectory();
        $completed = false;

        try {
            self::makeDirectory($applicationRoot . '/resources');
            self::makeDirectory($applicationRoot . '/src');
            foreach ([
                'ApplicationGraph.php' => 'packages/application-graph/src/Model/ApplicationGraph.php',
                'CommandSpecificationSet.php' => 'packages/console/src/CommandSpecificationSet.php',
                'CompositionArtifact.php' => 'packages/composition/src/Build/CompositionArtifact.php',
                'HanteiReport.php' => 'packages/hantei/src/HanteiReport.php',
                'CompositionInspectCommandHandler.php' => 'packages/console-commands/src/Handler/CompositionInspectCommandHandler.php',
                'GraphInspectCommandHandler.php' => 'packages/console-commands/src/Handler/GraphInspectCommandHandler.php',
                'QualityInspectCommandHandler.php' => 'packages/console-commands/src/Handler/QualityInspectCommandHandler.php',
                'ReadOnlyFrameworkCommandFamily.php' => 'packages/console-commands/src/ReadOnlyFrameworkCommandFamily.php',
            ] as $target => $source) {
                self::assertTrue(copy($repository . '/' . $source, $applicationRoot . '/src/' . $target));
            }
            self::assertTrue(copy(
                $repository . '/packages/console-commands/resources/kongono.commands.json',
                $applicationRoot . '/resources/kongono.commands.json',
            ));
            self::assertTrue(copy(
                $repository . '/tests/Fixture/Phase8F/kongono.composition.json',
                $applicationRoot . '/kongono.composition.json',
            ));
            self::writeFile($applicationRoot . '/composer.json', "{\"name\":\"example/phase8f\",\"description\":\"SECRET-PROJECT-SENTINEL\"}\n");
            self::writeFile($applicationRoot . '/composer.lock', "{\"packages\":[]}\n");

            $script = str_replace(
                ['{{REPOSITORY}}', '{{APPLICATION_ROOT}}'],
                [var_export(str_replace('\\', '/', $repository), true), var_export(str_replace('\\', '/', $applicationRoot), true)],
                self::externalProofScript(),
            );
            $scriptPath = $applicationRoot . '/phase8f-proof.php';
            self::writeFile($scriptPath, $script);

            $lint = self::runProcess([PHP_BINARY, '-l', $scriptPath], $applicationRoot, 'outer-lint', 120);
            self::assertSame(0, $lint['exit'], $lint['stderr'] . $lint['stdout']);
            $proof = self::runProcess([PHP_BINARY, $scriptPath], $applicationRoot, 'outer-proof', 240);
            self::assertSame(0, $proof['exit'], $proof['stderr'] . $proof['stdout']);
            $evidence = self::object(json_decode($proof['stdout'], true, flags: JSON_THROW_ON_ERROR));
            self::assertSame(['lint' => 0, 'phpstan' => 0, 'style' => 0], self::object($evidence['generated_gates'] ?? null));
            self::assertSame([
                'framework:composition',
                'framework:graph',
                'framework:quality',
            ], $evidence['bindings'] ?? null);
            $results = self::object($evidence['results'] ?? null);
            self::assertSame([
                'node_count' => 0,
                'node_kind_count' => 0,
                'relation_count' => 0,
                'relation_kind_count' => 0,
            ], self::object($results['framework:graph'] ?? null));
            self::assertSame([
                'blocked_count' => 0,
                'check_count' => 8,
                'fail_count' => 0,
                'pass_count' => 8,
                'verdict' => 'PASS',
            ], self::object($results['framework:quality'] ?? null));
            $composition = self::object($results['framework:composition'] ?? null);
            self::assertSame([
                'artifact_sha256',
                'builder_sha256',
                'model_sha256',
                'source_set_sha256',
                'source_sha256',
            ], array_keys($composition));
            foreach ($composition as $digest) {
                self::assertIsString($digest);
                self::assertMatchesRegularExpression('/\A[a-f0-9]{64}\z/D', $digest);
            }
            self::assertTrue($evidence['ordinary_constructors']);
            self::assertTrue($evidence['typed_inputs']);
            self::assertTrue($evidence['presentation_parity']);
            self::assertTrue($evidence['nondisclosure']);
            self::assertTrue($evidence['operation_closed']);
            self::assertTrue($evidence['application_retired']);
            self::assertTrue($evidence['generated_removed']);
            $completed = true;
        } finally {
            if ($completed) {
                self::removeTree($applicationRoot);
            }
        }

        self::assertDirectoryDoesNotExist($applicationRoot);
    }

    private static function externalProofScript(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

$repository = {{REPOSITORY}};
$applicationRoot = {{APPLICATION_ROOT}};
$classMap = [
    'Kongono\\ApplicationGraph\\Model\\ApplicationGraph' => $applicationRoot . '/src/ApplicationGraph.php',
    'Kongono\\Console\\CommandSpecificationSet' => $applicationRoot . '/src/CommandSpecificationSet.php',
    'Kongono\\Composition\\Build\\CompositionArtifact' => $applicationRoot . '/src/CompositionArtifact.php',
    'Kongono\\Hantei\\HanteiReport' => $applicationRoot . '/src/HanteiReport.php',
    'Kongono\\ConsoleCommands\\Handler\\CompositionInspectCommandHandler' => $applicationRoot . '/src/CompositionInspectCommandHandler.php',
    'Kongono\\ConsoleCommands\\Handler\\GraphInspectCommandHandler' => $applicationRoot . '/src/GraphInspectCommandHandler.php',
    'Kongono\\ConsoleCommands\\Handler\\QualityInspectCommandHandler' => $applicationRoot . '/src/QualityInspectCommandHandler.php',
    'Kongono\\ConsoleCommands\\ReadOnlyFrameworkCommandFamily' => $applicationRoot . '/src/ReadOnlyFrameworkCommandFamily.php',
];
require $repository . '/vendor/autoload.php';
spl_autoload_register(static function (string $class) use ($classMap): void {
    if (isset($classMap[$class])) {
        require $classMap[$class];
    }
}, true, true);

function expect(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

/** @param list<string> $command @return array{exit: int, stdout: string, stderr: string} */
function runCommand(array $command, string $stage, string $captureRoot, ?string $workingDirectory = null): array
{
    $capture = $captureRoot . '/capture-' . $stage . '-' . bin2hex(random_bytes(8));
    if (!mkdir($capture, 0700)) {
        throw new RuntimeException('Process capture directory could not be created.');
    }
    $stdoutPath = $capture . '/stdout.bin';
    $stderrPath = $capture . '/stderr.bin';
    foreach ([$stdoutPath, $stderrPath] as $path) {
        $handle = fopen($path, 'x');
        if (false === $handle) {
            throw new RuntimeException('Process capture file could not be created.');
        }
        fclose($handle);
    }
    $evidenceRoot = getenv('KONGONO_PHASE8F_EVIDENCE_DIR');
    if (false === $evidenceRoot) {
        $evidenceRoot = $captureRoot;
    }
    if (!is_dir($evidenceRoot) || is_link($evidenceRoot)) {
        throw new RuntimeException('Process evidence root is invalid.');
    }
    $sidecarPath = $evidenceRoot . '/phase8f-' . $stage . '-' . bin2hex(random_bytes(12)) . '.json';
    $started = gmdate('c');
    $exit = null;
    $timedOut = false;
    $retired = false;
    $failure = null;
    $pipes = [];
    try {
        $process = proc_open($command, [1 => ['file', $stdoutPath, 'w'], 2 => ['file', $stderrPath, 'w']], $pipes, $workingDirectory);
        if (!is_resource($process)) {
            throw new RuntimeException('Process start failed.');
        }
        $deadline = microtime(true) + 120;
        do {
            $status = proc_get_status($process);
            if (!$status['running']) {
                break;
            }
            if (microtime(true) >= $deadline) {
                $timedOut = true;
                break;
            }
            usleep(100000);
        } while (true);
        if ($timedOut) {
            if ('Windows' !== PHP_OS_FAMILY) {
                if (!proc_terminate($process)) {
                    throw new RuntimeException('Process child could not be terminated.');
                }
            } else {
                $systemRoot = getenv('SystemRoot');
                if (false === $systemRoot) {
                    throw new RuntimeException('Process system root is unavailable.');
                }
                $killer = proc_open(
                    [rtrim($systemRoot, '/\\') . '/System32/taskkill.exe', '/PID', (string) $status['pid'], '/T', '/F'],
                    [1 => ['file', $capture . '/retire.stdout.bin', 'w'], 2 => ['file', $capture . '/retire.stderr.bin', 'w']],
                    $killerPipes,
                );
                if (!is_resource($killer)) {
                    throw new RuntimeException('Process tree retirement could not start.');
                }
                $killerDeadline = microtime(true) + 10;
                do {
                    $killerStatus = proc_get_status($killer);
                    if (!$killerStatus['running']) {
                        break;
                    }
                    if (microtime(true) >= $killerDeadline) {
                        proc_terminate($killer);
                        throw new RuntimeException('Process tree retirement timed out.');
                    }
                    usleep(100000);
                } while (true);
                $killerExit = proc_close($killer);
                if (0 !== ($killerStatus['exitcode'] >= 0 ? $killerStatus['exitcode'] : $killerExit)) {
                    throw new RuntimeException('Process tree retirement failed.');
                }
            }
            $retireDeadline = microtime(true) + 10;
            do {
                $status = proc_get_status($process);
                if (!$status['running']) {
                    break;
                }
                if (microtime(true) >= $retireDeadline) {
                    throw new RuntimeException('Process child retirement is uncertain.');
                }
                usleep(100000);
            } while (true);
        }
        $retired = true;
        $closedExit = proc_close($process);
        $exit = $status['exitcode'] >= 0 ? $status['exitcode'] : $closedExit;
        if ($exit < 0) {
            throw new RuntimeException('Process child exit status is unavailable.');
        }
    } catch (\Throwable $caught) {
        $failure = $caught;
    }
    clearstatcache(true, $stdoutPath);
    clearstatcache(true, $stderrPath);
    $stdoutBytes = filesize($stdoutPath);
    $stderrBytes = filesize($stderrPath);
    $record = [
        'stage' => $stage,
        'command' => $command,
        'started_utc' => $started,
        'ended_utc' => gmdate('c'),
        'exit' => $exit,
        'timed_out' => $timedOut,
        'retired' => $retired,
        'stdout_bytes' => $stdoutBytes,
        'stderr_bytes' => $stderrBytes,
        'failure' => $failure?->getMessage(),
    ];
    $sidecar = fopen($sidecarPath, 'x');
    if (false === $sidecar) {
        throw new RuntimeException('Process sidecar could not be created.');
    }
    $encoded = json_encode($record, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    if (strlen($encoded) !== fwrite($sidecar, $encoded)) {
        fclose($sidecar);
        throw new RuntimeException('Process sidecar write failed.');
    }
    fclose($sidecar);
    if (null !== $failure) {
        throw $failure;
    }
    if ($timedOut) {
        throw new RuntimeException('Process deadline exceeded after child retirement.');
    }
    if (!is_int($stdoutBytes) || !is_int($stderrBytes) || $stdoutBytes > 8388608 || $stderrBytes > 8388608) {
        throw new RuntimeException('Process capture exceeded the 8 MiB stream bound.');
    }
    $stdout = file_get_contents($stdoutPath);
    $stderr = file_get_contents($stderrPath);
    expect(is_string($stdout) && is_string($stderr), 'Process output was unavailable.');
    if (0 === $exit) {
        foreach ([$stdoutPath, $stderrPath] as $path) {
            expect(unlink($path), 'Process capture cleanup failed.');
        }
        expect(rmdir($capture), 'Process capture directory cleanup failed.');
    }

    return ['exit' => $exit, 'stdout' => $stdout, 'stderr' => $stderr];
}

$consoleArtifact = (new Kongono\ConsoleBuild\Build\ConsoleArtifactBuilder())->build(
    $applicationRoot,
    'resources/kongono.commands.json',
);
$consoleArtifact = (new Kongono\ConsoleBuild\Build\ConsoleArtifactValidator())->validateCurrent(
    $applicationRoot,
    'resources/kongono.commands.json',
    $consoleArtifact->canonicalBytes(),
);
$specifications = $consoleArtifact->model()->specifications();

$composition = (new Kongono\Composition\Build\CompositionArtifactBuilder())->build($applicationRoot);
$composition = (new Kongono\Composition\Build\CompositionArtifactValidator())->validateCurrent(
    $applicationRoot,
    $composition->canonicalBytes(),
);
$wiring = (new Kongono\Composition\Generated\GeneratedWiringGenerator())->generate(
    $applicationRoot,
    $composition->canonicalBytes(),
);
$wiring = (new Kongono\Composition\Generated\GeneratedWiringValidator())->validateCurrent(
    $applicationRoot,
    $composition->canonicalBytes(),
    $wiring->canonicalBytes(),
);
$generatedPath = $applicationRoot . '/kongono-wiring-v1-' . $wiring->wiringSha256() . '.php';
expect(strlen($wiring->canonicalBytes()) === file_put_contents($generatedPath, $wiring->canonicalBytes()), 'Generated write failed.');

$phpstanCache = $applicationRoot . '/phpstan-cache';
expect(mkdir($phpstanCache, 0700), 'Private PHPStan cache directory could not be created.');
$phpstanWrapper = $applicationRoot . '/phpstan-wrapper.neon';
$wrapperBytes = "includes:\n    - " . json_encode(str_replace('\\', '/', $repository) . '/phpstan.neon.dist', JSON_THROW_ON_ERROR)
    . "\nparameters:\n    tmpDir: " . json_encode($phpstanCache, JSON_THROW_ON_ERROR) . "\n";
expect(strlen($wrapperBytes) === file_put_contents($phpstanWrapper, $wrapperBytes), 'Private PHPStan wrapper could not be created.');

$gates = [
    'lint' => runCommand([PHP_BINARY, '-l', $generatedPath], 'nested-lint', $applicationRoot),
    'phpstan' => runCommand([
        PHP_BINARY,
        $repository . '/vendor/bin/phpstan',
        'analyse',
        '--configuration=' . $phpstanWrapper,
        '--level=max',
        '--no-progress',
        '--debug',
        '--autoload-file=' . $repository . '/vendor/autoload.php',
        $generatedPath,
    ], 'nested-phpstan', $applicationRoot, $applicationRoot),
    'style' => runCommand([
        PHP_BINARY,
        $repository . '/vendor/bin/php-cs-fixer',
        'fix',
        '--config=' . $repository . '/.php-cs-fixer.dist.php',
        '--dry-run',
        '--diff',
        '--using-cache=no',
        $generatedPath,
    ], 'nested-style', $applicationRoot),
];
foreach ($gates as $name => $gate) {
    expect(0 === $gate['exit'], $name . " failed:\n" . $gate['stdout'] . $gate['stderr']);
}

$generatedBytes = $wiring->canonicalBytes();
$ordinaryConstructors = str_contains($generatedBytes, 'new \\Kongono\\ConsoleCommands\\Handler\\CompositionInspectCommandHandler(')
    && str_contains($generatedBytes, 'new \\Kongono\\ConsoleCommands\\Handler\\GraphInspectCommandHandler(')
    && str_contains($generatedBytes, 'new \\Kongono\\ConsoleCommands\\Handler\\QualityInspectCommandHandler(')
    && str_contains($generatedBytes, 'new \\Kongono\\ConsoleCommands\\ReadOnlyFrameworkCommandFamily(')
    && !str_contains($generatedBytes, 'Reflection')
    && !str_contains($generatedBytes, 'DependencyResolver')
    && !str_contains($generatedBytes, 'eval(');
$typedInputs = str_contains($generatedBytes, '\\Kongono\\ApplicationGraph\\Model\\ApplicationGraph $input1')
    && str_contains($generatedBytes, '\\Kongono\\Composition\\Build\\CompositionArtifact $input2')
    && str_contains($generatedBytes, '\\Kongono\\Console\\CommandSpecificationSet $input3')
    && str_contains($generatedBytes, '\\Kongono\\Hantei\\HanteiReport $input4');

$checks = [];
foreach (Kongono\Hantei\HanteiCheckEvidence::CHECKS as $check => $script) {
    $checks[] = new Kongono\Hantei\HanteiCheckEvidence(
        $check,
        $script,
        Kongono\Hantei\HanteiCheckOutcome::Pass,
        0,
        0,
        str_repeat('a', 64),
        0,
        str_repeat('b', 64),
        null,
    );
}
$report = new Kongono\Hantei\HanteiReport(
    Kongono\Hantei\HanteiVerdict::Pass,
    str_repeat('c', 64),
    str_repeat('d', 64),
    str_repeat('e', 64),
    str_repeat('f', 64),
    PHP_VERSION,
    str_repeat('1', 64),
    'git version',
    $checks,
    [],
);
$graph = new Kongono\ApplicationGraph\Model\ApplicationGraph([], []);
$loaded = (new Kongono\Composition\Runtime\GeneratedWiringLoader())->loadCurrent(
    $applicationRoot,
    $composition->canonicalBytes(),
    $generatedPath,
);
$application = $loaded->start($graph, $composition, $specifications, $report);
$rootMethod = (new ReflectionClass($loaded->applicationClass()))->getMethod('applicationRoot1');
$family = $rootMethod->invoke($application);
expect($family instanceof Kongono\ConsoleCommands\ReadOnlyFrameworkCommandFamily, 'Generated root type mismatch.');

$bindings = array_map(
    static fn(Kongono\ConsoleRuntime\CommandHandlerBinding $binding): string => $binding->identifier()->value(),
    $family->handlers()->bindings(),
);
$dispatcher = new Kongono\ConsoleRuntime\CommandDispatcher($family->handlers());
$cancellation = new class implements Kongono\Console\CommandCancellation {
    public function isRequested(): bool
    {
        return false;
    }
};
$humanPresenter = new Kongono\ConsolePresentation\HumanCommandPresenter();
$machinePresenter = new Kongono\ConsolePresentation\MachineCommandPresenter();
$results = [];
$presentationParity = true;
$documents = '';
foreach ($family->specifications()->commands() as $specification) {
    $invocation = new Kongono\Console\CommandInvocation(
        $specification->definition(),
        [],
        Kongono\Console\CommandExecutionIntent::Observe,
        Kongono\Console\CommandInteractionMode::Disabled,
    );
    $result = $dispatcher->dispatch($invocation, $cancellation);
    expect(Kongono\Console\CommandOutcome::Succeeded === $result->outcome(), 'Dispatch did not succeed.');
    $record = $result->data();
    expect($record instanceof Kongono\Console\CommandResultRecord, 'Result record missing.');
    $values = [];
    foreach ($record->fields() as $field) {
        expect(Kongono\Console\CommandResultFieldDisclosure::Safe === $field->disclosure(), 'Unsafe result field.');
        $values[$field->name()->value()] = $field->value()->value();
    }
    $identifier = $specification->definition()->identifier()->value();
    $results[$identifier] = $values;
    $human = $humanPresenter->presentResult($result);
    $machine = $machinePresenter->presentResult($result);
    $machineData = json_decode($machine->stdout(), true, flags: JSON_THROW_ON_ERROR);
    $machineValues = [];
    foreach ($machineData['data']['fields'] as $field) {
        $machineValues[$field['name']] = $field['value'];
    }
    $presentationParity = $presentationParity
        && 0 === $human->exitStatus()->value
        && 0 === $machine->exitStatus()->value
        && $values === $machineValues;
    foreach ($record->fields() as $field) {
        $presentationParity = $presentationParity
            && str_contains($human->stdout(), $field->label()->value() . ': ' . (string) $field->value()->value());
    }
    $documents .= $human->stdout() . $human->stderr() . $machine->stdout() . $machine->stderr();
}

$operation = $application->beginOperation();
$operation->close();
$operationClosed = $operation->isClosed();
$application->shutdown();
$applicationRetired = $application->isRetired();
expect(unlink($generatedPath), 'Generated cleanup failed.');

$nondisclosure = true;
foreach (['SECRET-PROJECT-SENTINEL', 'ApplicationGraph', 'ReadOnlyFrameworkCommandFamily', $applicationRoot, 'composer.json'] as $sentinel) {
    $nondisclosure = $nondisclosure && !str_contains($documents, $sentinel);
}

echo json_encode([
    'generated_gates' => array_map(static fn(array $gate): int => $gate['exit'], $gates),
    'bindings' => $bindings,
    'results' => $results,
    'ordinary_constructors' => $ordinaryConstructors,
    'typed_inputs' => $typedInputs,
    'presentation_parity' => $presentationParity,
    'nondisclosure' => $nondisclosure,
    'operation_closed' => $operationClosed,
    'application_retired' => $applicationRetired,
    'generated_removed' => !file_exists($generatedPath),
], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
PHP;
    }

    /**
     * @param list<string> $command
     * @return array{exit: int, stdout: string, stderr: string}
     */
    private static function runProcess(array $command, string $root, string $stage, int $timeoutSeconds): array
    {
        $capture = $root . '/capture-' . $stage . '-' . bin2hex(random_bytes(8));
        if (!mkdir($capture, 0700)) {
            throw new RuntimeException('External Phase 8F capture directory could not be created.');
        }
        $stdoutPath = $capture . '/stdout.bin';
        $stderrPath = $capture . '/stderr.bin';
        foreach ([$stdoutPath, $stderrPath] as $path) {
            $handle = fopen($path, 'x');
            if (false === $handle) {
                throw new RuntimeException('External Phase 8F capture file could not be created.');
            }
            fclose($handle);
        }
        $evidenceRoot = getenv('KONGONO_PHASE8F_EVIDENCE_DIR');
        if (false === $evidenceRoot) {
            $evidenceRoot = $root;
        }
        if (!is_dir($evidenceRoot) || is_link($evidenceRoot)) {
            throw new RuntimeException('External Phase 8F evidence root is invalid.');
        }
        $sidecarPath = $evidenceRoot . '/phase8f-' . $stage . '-' . bin2hex(random_bytes(12)) . '.json';
        $started = gmdate('c');
        $exit = null;
        $timedOut = false;
        $retired = false;
        $failure = null;
        $pipes = [];
        try {
            $process = proc_open($command, [1 => ['file', $stdoutPath, 'w'], 2 => ['file', $stderrPath, 'w']], $pipes);
            if (!is_resource($process)) {
                throw new RuntimeException('External Phase 8F proof could not start.');
            }
            $deadline = microtime(true) + $timeoutSeconds;
            do {
                $status = proc_get_status($process);
                if (!$status['running']) {
                    break;
                }
                if (microtime(true) >= $deadline) {
                    $timedOut = true;
                    break;
                }
                usleep(100000);
            } while (true);
            if ($timedOut) {
                self::retireProcessTree($process, $status['pid'], $capture);
                $status = proc_get_status($process);
                if ($status['running']) {
                    throw new RuntimeException('External Phase 8F child retirement is uncertain.');
                }
            }
            $retired = true;
            $closedExit = proc_close($process);
            $exit = $status['exitcode'] >= 0 ? $status['exitcode'] : $closedExit;
            if ($exit < 0) {
                throw new RuntimeException('External Phase 8F child exit status is unavailable.');
            }
        } catch (\Throwable $caught) {
            $failure = $caught;
        }
        clearstatcache(true, $stdoutPath);
        clearstatcache(true, $stderrPath);
        $stdoutBytes = filesize($stdoutPath);
        $stderrBytes = filesize($stderrPath);
        $record = [
            'stage' => $stage,
            'command' => $command,
            'started_utc' => $started,
            'ended_utc' => gmdate('c'),
            'exit' => $exit,
            'timed_out' => $timedOut,
            'retired' => $retired,
            'stdout_bytes' => $stdoutBytes,
            'stderr_bytes' => $stderrBytes,
            'failure' => $failure?->getMessage(),
        ];
        $sidecar = fopen($sidecarPath, 'x');
        if (false === $sidecar) {
            throw new RuntimeException('External Phase 8F sidecar could not be created.');
        }
        $encoded = json_encode($record, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        if (strlen($encoded) !== fwrite($sidecar, $encoded)) {
            fclose($sidecar);
            throw new RuntimeException('External Phase 8F sidecar write failed.');
        }
        fclose($sidecar);
        if (null !== $failure) {
            throw $failure;
        }
        if ($timedOut) {
            throw new RuntimeException('External Phase 8F deadline exceeded after child retirement.');
        }
        if (!is_int($stdoutBytes) || !is_int($stderrBytes) || $stdoutBytes > 8388608 || $stderrBytes > 8388608) {
            throw new RuntimeException('External Phase 8F capture exceeded the 8 MiB stream bound.');
        }
        $stdout = file_get_contents($stdoutPath);
        $stderr = file_get_contents($stderrPath);
        if (!is_string($stdout) || !is_string($stderr)) {
            throw new RuntimeException('External Phase 8F proof output was unavailable.');
        }
        if (0 === $exit) {
            foreach ([$stdoutPath, $stderrPath] as $path) {
                if (!unlink($path)) {
                    throw new RuntimeException('External Phase 8F capture cleanup failed.');
                }
            }
            if (!rmdir($capture)) {
                throw new RuntimeException('External Phase 8F capture directory cleanup failed.');
            }
        }

        return ['exit' => $exit, 'stdout' => $stdout, 'stderr' => $stderr];
    }

    /** @param resource $process */
    private static function retireProcessTree($process, int $pid, string $capture): void
    {
        if ('Windows' !== PHP_OS_FAMILY) {
            if (!proc_terminate($process)) {
                throw new RuntimeException('External Phase 8F child could not be terminated.');
            }
        } else {
            $systemRoot = getenv('SystemRoot');
            if (false === $systemRoot) {
                throw new RuntimeException('External Phase 8F system root is unavailable.');
            }
            $killer = proc_open(
                [rtrim($systemRoot, '/\\') . '/System32/taskkill.exe', '/PID', (string) $pid, '/T', '/F'],
                [1 => ['file', $capture . '/retire.stdout.bin', 'w'], 2 => ['file', $capture . '/retire.stderr.bin', 'w']],
                $killerPipes,
            );
            if (!is_resource($killer)) {
                throw new RuntimeException('External Phase 8F tree retirement could not start.');
            }
            $killerDeadline = microtime(true) + 10;
            do {
                $killerStatus = proc_get_status($killer);
                if (!$killerStatus['running']) {
                    break;
                }
                if (microtime(true) >= $killerDeadline) {
                    proc_terminate($killer);
                    throw new RuntimeException('External Phase 8F tree retirement timed out.');
                }
                usleep(100000);
            } while (true);
            $killerExit = proc_close($killer);
            if (0 !== ($killerStatus['exitcode'] >= 0 ? $killerStatus['exitcode'] : $killerExit)) {
                throw new RuntimeException('External Phase 8F tree retirement failed.');
            }
        }
        $deadline = microtime(true) + 10;
        do {
            $status = proc_get_status($process);
            if (!$status['running']) {
                return;
            }
            if (microtime(true) >= $deadline) {
                throw new RuntimeException('External Phase 8F child retirement is uncertain.');
            }
            usleep(100000);
        } while (true);
    }

    /** @return array<string, mixed> */
    private static function object(mixed $value): array
    {
        if (!is_array($value) || array_is_list($value)) {
            throw new RuntimeException('External Phase 8F proof returned an invalid object.');
        }

        $object = [];
        foreach ($value as $key => $member) {
            if (!is_string($key)) {
                throw new RuntimeException('External Phase 8F proof returned a non-string object key.');
            }
            $object[$key] = $member;
        }

        return $object;
    }

    private static function temporaryDirectory(): string
    {
        $path = str_replace('\\', '/', sys_get_temp_dir()) . '/kongono-phase8f-' . bin2hex(random_bytes(12));
        self::makeDirectory($path);

        return $path;
    }

    private static function makeDirectory(string $path): void
    {
        self::assertTrue(mkdir($path, 0700, true));
    }

    private static function writeFile(string $path, string $bytes): void
    {
        self::assertSame(strlen($bytes), file_put_contents($path, $bytes));
    }

    private static function removeTree(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );
        /** @var SplFileInfo $entry */
        foreach ($iterator as $entry) {
            if ($entry->isDir() && !$entry->isLink()) {
                self::assertTrue(rmdir($entry->getPathname()));
            } else {
                self::assertTrue(unlink($entry->getPathname()));
            }
        }
        self::assertTrue(rmdir($directory));
    }
}
