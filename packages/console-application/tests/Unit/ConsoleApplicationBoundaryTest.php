<?php

declare(strict_types=1);

namespace Kongono\ConsoleApplication\Tests\Unit;

use Kongono\ApplicationGraphArtifact\Artifact\ApplicationGraphArtifactLoader;
use Kongono\AssetApplication\Workflow\AssetApplicationWorkflow;
use Kongono\Composition\Workflow\ApplicationBuildWorkflow;
use Kongono\ConsoleApplication\Handler\ApplicationBuildCommandHandler;
use Kongono\ConsoleApplication\Handler\ApplicationGraphInspectCommandHandler;
use Kongono\ConsoleApplication\Handler\AssetBuildCommandHandler;
use Kongono\ConsoleApplication\Handler\AssetCheckCommandHandler;
use Kongono\ConsoleApplication\Handler\AssetResolveCommandHandler;
use Kongono\ConsoleApplication\Handler\ExtensionInspectCommandHandler;
use Kongono\ConsoleApplication\Handler\ObservabilityCheckCommandHandler;
use Kongono\ConsoleApplication\Handler\ObservabilityInspectCommandHandler;
use Kongono\ConsoleApplication\Handler\SecurityCheckCommandHandler;
use Kongono\ConsoleApplication\Handler\SecurityInspectCommandHandler;
use Kongono\ConsoleApplication\Handler\ViewBuildCommandHandler;
use Kongono\ConsoleApplication\Handler\ViewCheckCommandHandler;
use Kongono\ConsoleApplication\Handler\ViewInspectCommandHandler;
use Kongono\ConsoleApplication\Internal\KongoCommandValidator;
use Kongono\ConsoleApplication\KongoCommand;
use Kongono\ConsoleBuild\Build\ConsoleArtifactBuilder;
use Kongono\ConsoleRuntime\CommandHandlerBinding;
use Kongono\ConsoleRuntime\CommandHandlerSet;
use Kongono\ExtensionInspection\Loading\ExtensionInspectionLoader;
use Kongono\ObservabilityBuild\Workflow\ObservabilityBuildWorkflow;
use Kongono\SecurityPolicyBuild\Workflow\SecurityPolicyBuildWorkflow;
use Kongono\ViewApplication\Workflow\ViewApplicationBuildWorkflow;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class ConsoleApplicationBoundaryTest extends TestCase
{
    public function testManifestDependenciesBinaryAndInventoryAreExact(): void
    {
        $root = dirname(__DIR__, 2);
        $manifest = json_decode((string) file_get_contents($root . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        if (!is_array($manifest)) {
            self::fail('Package manifest must decode to an object.');
        }
        self::assertSame('kongono/console-application', $manifest['name']);
        self::assertSame([
            'kongono/api-metadata' => '*',
            'kongono/application-graph-artifact' => '*',
            'kongono/asset-application' => '*',
            'kongono/composition' => '*',
            'kongono/console' => '*',
            'kongono/console-presentation' => '*',
            'kongono/console-runtime' => '*',
            'kongono/diagnostics' => '*',
            'kongono/extension-inspection' => '*',
            'kongono/observability-build' => '*',
            'kongono/security-policy-build' => '*',
            'kongono/view-application' => '*',
            'php' => '>=8.5.0 <8.6.0',
        ], $manifest['require']);
        self::assertSame(['kongono/console-build' => '*'], $manifest['require-dev']);
        self::assertSame(['bin/kongo'], $manifest['bin']);
        self::assertCount(25, self::phpFiles($root . '/src'));
        self::assertCount(20, self::phpFiles($root . '/tests'));
        self::assertSame(['kongo'], array_map('basename', self::ordinaryFiles($root . '/bin')));
    }

    public function testSourceModelEnumAndValidatorDefinitionsAreExact(): void
    {
        $root = dirname(__DIR__, 2);
        $temporary = str_replace('\\', '/', sys_get_temp_dir()) . '/kongono-phase8g-command-source-' . bin2hex(random_bytes(12));
        self::assertTrue(mkdir($temporary, 0700, true));
        $source = (string) file_get_contents($root . '/resources/kongo.commands.json');
        self::assertSame(strlen($source), file_put_contents($temporary . '/kongono.commands.json', $source));
        try {
            $artifact = (new ConsoleArtifactBuilder())->build($temporary, 'kongono.commands.json');
        } finally {
            self::assertTrue(unlink($temporary . '/kongono.commands.json'));
            self::assertTrue(rmdir($temporary));
        }
        $specifications = $artifact->model()->specifications();
        $handlers = new CommandHandlerSet([
            new CommandHandlerBinding(
                new \Kongono\Console\CommandIdentifier(KongoCommand::ApplicationGraphInspect->value),
                new ApplicationGraphInspectCommandHandler(new ApplicationGraphArtifactLoader()),
            ),
            new CommandHandlerBinding(
                new \Kongono\Console\CommandIdentifier(KongoCommand::ApplicationBuild->value),
                new ApplicationBuildCommandHandler(new ApplicationBuildWorkflow()),
            ),
            new CommandHandlerBinding(
                new \Kongono\Console\CommandIdentifier(KongoCommand::AssetBuild->value),
                new AssetBuildCommandHandler(new AssetApplicationWorkflow()),
            ),
            new CommandHandlerBinding(
                new \Kongono\Console\CommandIdentifier(KongoCommand::AssetCheck->value),
                new AssetCheckCommandHandler(new AssetApplicationWorkflow()),
            ),
            new CommandHandlerBinding(
                new \Kongono\Console\CommandIdentifier(KongoCommand::AssetResolve->value),
                new AssetResolveCommandHandler(new AssetApplicationWorkflow()),
            ),
            new CommandHandlerBinding(
                new \Kongono\Console\CommandIdentifier(KongoCommand::ExtensionInspect->value),
                new ExtensionInspectCommandHandler(new ExtensionInspectionLoader()),
            ),
            new CommandHandlerBinding(
                new \Kongono\Console\CommandIdentifier(KongoCommand::ObservabilityCheck->value),
                new ObservabilityCheckCommandHandler(new ObservabilityBuildWorkflow()),
            ),
            new CommandHandlerBinding(
                new \Kongono\Console\CommandIdentifier(KongoCommand::ObservabilityInspect->value),
                new ObservabilityInspectCommandHandler(new ObservabilityBuildWorkflow()),
            ),
            new CommandHandlerBinding(
                new \Kongono\Console\CommandIdentifier(KongoCommand::SecurityCheck->value),
                new SecurityCheckCommandHandler(new SecurityPolicyBuildWorkflow()),
            ),
            new CommandHandlerBinding(
                new \Kongono\Console\CommandIdentifier(KongoCommand::SecurityInspect->value),
                new SecurityInspectCommandHandler(new SecurityPolicyBuildWorkflow()),
            ),
            new CommandHandlerBinding(
                new \Kongono\Console\CommandIdentifier(KongoCommand::ViewBuild->value),
                new ViewBuildCommandHandler(new ViewApplicationBuildWorkflow()),
            ),
            new CommandHandlerBinding(
                new \Kongono\Console\CommandIdentifier(KongoCommand::ViewCheck->value),
                new ViewCheckCommandHandler(new ViewApplicationBuildWorkflow()),
            ),
            new CommandHandlerBinding(
                new \Kongono\Console\CommandIdentifier(KongoCommand::ViewInspect->value),
                new ViewInspectCommandHandler(new ViewApplicationBuildWorkflow()),
            ),
        ]);
        (new KongoCommandValidator())->validate($specifications, $handlers);

        $commands = $specifications->commands();
        self::assertCount(13, $commands);
        self::assertSame(
            ['application-graph:inspect', 'application:build', 'asset:build', 'asset:check', 'asset:resolve', 'extension:inspect', 'observability:check', 'observability:inspect', 'security:check', 'security:inspect', 'view:build', 'view:check', 'view:inspect'],
            array_map(static fn($command): string => $command->definition()->identifier()->value(), $commands),
        );
        self::assertSame(
            [
                'Inspect one authoritative application graph artifact',
                'Build exact current application composition outputs',
                'Build exact current deterministic native assets',
                'Check exact current deterministic native assets',
                'Resolve one exact current asset under explicit browser policy',
                'Inspect detached extension platform evidence',
                'Check exact current observability configuration evidence',
                'Inspect planned observability configuration evidence',
                'Check exact current security policy evidence',
                'Inspect planned security policy evidence',
                'Build exact current compiled-view application artifact',
                'Check exact current compiled-view application artifact',
                'Inspect planned compiled-view application evidence',
            ],
            array_map(static fn($command): string => $command->definition()->summary()->value(), $commands),
        );
        foreach ($commands as $position => $command) {
            $expectedInputs = match ($position) {
                0 => ['artifact-path'],
                2, 3, 10, 11, 12 => ['application-root', 'output-directory', 'source-file'],
                4 => ['application-root', 'cache-policy', 'integrity-policy', 'logical-identity', 'output-directory', 'preload-policy', 'public-base-url', 'source-file'],
                5 => ['declaration-artifact-path', 'generated-wiring-path'],
                default => ['application-root', 'output-directory'],
            };
            self::assertSame($expectedInputs, array_map(
                static fn($input): string => $input->name()->value(),
                $command->inputs(),
            ));
        }
    }

    public function testOnlyBinReadsProcessGlobalsOrExitsAndNoForbiddenMechanismExists(): void
    {
        $root = dirname(__DIR__, 2);
        $source = '';
        foreach (self::phpFiles($root . '/src') as $path) {
            $source .= (string) file_get_contents($path);
        }
        foreach (['$argv', '$_SERVER', '$GLOBALS', 'exit(', 'die(', 'getenv(', 'putenv(', 'ReflectionClass', 'scandir(', 'glob(', 'service locator', 'container->', 'eval(', 'proc_open(', 'shell_exec('] as $forbidden) {
            self::assertStringNotContainsStringIgnoringCase($forbidden, $source);
        }
        foreach (['STDIN', 'STDOUT', 'STDERR'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source);
        }

        $bin = (string) file_get_contents($root . '/bin/kongo');
        foreach (["\$GLOBALS['argv']", '$GLOBALS', 'STDOUT', 'STDERR', 'exit('] as $required) {
            self::assertStringContainsString($required, $bin);
        }
        self::assertStringNotContainsString('$_SERVER', $bin);
        self::assertStringNotContainsString('getenv(', $bin);
    }

    /** @return list<string> */
    private static function phpFiles(string $directory): array
    {
        return array_values(array_filter(self::ordinaryFiles($directory), static fn(string $path): bool => str_ends_with($path, '.php')));
    }

    /** @return list<string> */
    private static function ordinaryFiles(string $directory): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS));
        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $files[] = $file->getPathname();
            }
        }
        sort($files, SORT_STRING);

        return $files;
    }
}
