<?php

declare(strict_types=1);

namespace Kongono\Tests\Integration\Phase5G;

use App\Bootstrap;
use Kongono\Http\Message\Message\ServerRequest;
use Kongono\Kernel\Kernel;
use Kongono\Kernel\Lifecycle\KernelState;
use Kongono\Web\Application\WebApplication;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class MinimalApplicationSkeletonTest extends TestCase
{
    private const TEMPLATE_PATHS = [
        '.gitignore',
        'README.md',
        'bin/build.php',
        'bin/check.php',
        'bin/run.php',
        'composer.json',
        'composer.lock',
        'kongono.composition.json',
        'kongono.skeleton.json',
        'public/index.php',
        'src/Bootstrap.php',
        'src/Http/HomeHandler.php',
        'src/Http/NotFoundHandler.php',
        'src/Runtime.php',
        'var/kongono/build/.gitignore',
    ];

    public static function setUpBeforeClass(): void
    {
        $root = self::root();
        require_once $root . '/skeletons/minimal/src/Http/HomeHandler.php';
        require_once $root . '/skeletons/minimal/src/Http/NotFoundHandler.php';
        require_once $root . '/skeletons/minimal/src/Bootstrap.php';
        require_once $root . '/skeletons/minimal/src/Runtime.php';
    }

    public function testSkeletonInventoryOwnershipManifestAndComposerContractAreExact(): void
    {
        $skeleton = self::root() . '/skeletons/minimal';
        self::assertSame(self::TEMPLATE_PATHS, self::relativeFiles($skeleton));

        $ownership = self::json($skeleton . '/kongono.skeleton.json');
        self::assertSame(['schema', 'version', 'template_paths', 'derived_output_patterns'], array_keys($ownership));
        self::assertSame('kongono.application-skeleton', $ownership['schema']);
        self::assertSame(1, $ownership['version']);
        self::assertSame(self::TEMPLATE_PATHS, $ownership['template_paths']);
        self::assertSame([
            'var/kongono/build/kongono-composition-v1-*.json',
            'var/kongono/build/kongono-wiring-v1-*.php',
            'vendor/**',
        ], $ownership['derived_output_patterns']);

        $composer = self::json($skeleton . '/composer.json');
        self::assertIsArray($composer['scripts']);
        self::assertIsArray($composer['autoload']);
        self::assertIsArray($composer['autoload']['psr-4']);
        self::assertIsArray($composer['repositories']);
        self::assertIsArray($composer['repositories'][0]);
        self::assertIsArray($composer['repositories'][0]['options']);
        self::assertIsArray($composer['require']);
        self::assertIsArray($composer['config']);
        self::assertSame(['app:build', 'app:check', 'app:run'], array_keys($composer['scripts']));
        self::assertSame(['App\\' => 'src/'], $composer['autoload']['psr-4']);
        self::assertSame(false, $composer['repositories'][0]['options']['symlink']);
        self::assertSame('>=8.5.0 <8.6.0', $composer['require']['php']);
        self::assertSame([], $composer['config']['allow-plugins']);
        self::assertSame('dev', $composer['minimum-stability']);
        self::assertSame(true, $composer['prefer-stable']);

        $lock = self::json($skeleton . '/composer.lock');
        self::assertIsArray($lock['packages']);
        self::assertCount(19, $lock['packages']);
        $thirdParty = [];
        foreach ($lock['packages'] as $package) {
            self::assertIsArray($package);
            self::assertIsString($package['name']);
            if (!str_starts_with($package['name'], 'kongono/')) {
                $thirdParty[] = $package['name'];
            }
        }
        self::assertSame(['psr/http-factory', 'psr/http-message', 'psr/http-server-handler', 'psr/http-server-middleware'], $thirdParty);
        foreach ($lock['packages'] as $package) {
            self::assertIsArray($package);
            self::assertIsString($package['name']);
            if (str_starts_with($package['name'], 'kongono/')) {
                self::assertIsArray($package['dist']);
                self::assertIsString($package['dist']['url']);
                self::assertSame('path', $package['dist']['type']);
                self::assertStringStartsWith('../../packages/', $package['dist']['url']);
            }
        }
    }

    public function testCompositionDeclarationMatchesTheExactGeneratedWebProfile(): void
    {
        $source = self::json(self::root() . '/skeletons/minimal/kongono.composition.json');
        self::assertSame([
            ['contract' => 'Kongono\Kernel\Contract\KernelInterface'],
            ['contract' => 'Kongono\Web\Runtime\GeneratedWebRuntimeBootstrap'],
            ['contract' => 'Psr\Http\Server\RequestHandlerInterface'],
        ], $source['inputs']);
        self::assertSame([], $source['bindings']);
        self::assertSame([['kind' => 'application', 'contract' => 'Kongono\Web\Runtime\GeneratedWebRuntimeRoot']], $source['roots']);
        self::assertIsArray($source['services']);
        self::assertIsArray($source['services'][0]);
        self::assertIsArray($source['services'][1]);
        self::assertCount(2, $source['services']);
        self::assertSame('shutdown', $source['services'][0]['cleanup']);
        self::assertNull($source['services'][1]['cleanup']);
        self::assertSame('Kongono\Web\Runtime\GeneratedWebRuntimeRoot', $source['services'][1]['class']);
    }

    public function testRealRouteUnmatchedPipelineAndKernelOperationOwnership(): void
    {
        $kernel = new Kernel();
        $application = new WebApplication($kernel, Bootstrap::pipeline());

        $home = $application->handle(new ServerRequest('GET', '/'));
        self::assertSame(200, $home->getStatusCode());
        self::assertSame("Hello from Kongono.\n", (string) $home->getBody());
        self::assertSame(0, $kernel->activeOperationCount());

        $missing = $application->handle(new ServerRequest('GET', '/missing'));
        self::assertSame(404, $missing->getStatusCode());
        self::assertSame("Not Found.\n", (string) $missing->getBody());
        self::assertSame(0, $kernel->activeOperationCount());

        $application->shutdown();
        self::assertSame(KernelState::Shutdown, $kernel->state());
    }

    public function testCommandsAndWorkflowContainNoProhibitedRuntimeMechanism(): void
    {
        $root = self::root();
        $paths = [
            'packages/composition/src/Workflow/ApplicationBuildWorkflow.php',
            'skeletons/minimal/bin/build.php',
            'skeletons/minimal/bin/check.php',
            'skeletons/minimal/bin/run.php',
            'skeletons/minimal/public/index.php',
            'skeletons/minimal/src/Bootstrap.php',
            'skeletons/minimal/src/Runtime.php',
        ];
        $bytes = '';
        foreach ($paths as $path) {
            $source = file_get_contents($root . '/' . $path);
            self::assertIsString($source);
            $bytes .= $source;
        }
        foreach (['service locator', 'container', 'request-time reflection', 'scandir', 'glob(', 'DirectoryIterator', 'latest pointer', 'socket_', 'stream_socket_server', 'listen(', 'daemon', 'watcher', 'docker'] as $forbidden) {
            self::assertStringNotContainsStringIgnoringCase($forbidden, $bytes);
        }
        self::assertStringContainsString('CgiGeneratedFrontController::run($application)', $bytes);
        self::assertStringNotContainsString(' -b', $bytes);
    }

    /** @return array<string, mixed> */
    private static function json(string $path): array
    {
        $decoded = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($decoded);

        $object = [];
        foreach ($decoded as $key => $value) {
            if (!is_string($key)) {
                self::fail(sprintf('Expected a JSON object in %s.', $path));
            }
            $object[$key] = $value;
        }

        return $object;
    }

    /** @return list<string> */
    private static function relativeFiles(string $root): array
    {
        $paths = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));
        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $paths[] = substr(str_replace('\\', '/', $file->getPathname()), strlen($root) + 1);
            }
        }
        sort($paths, SORT_STRING);

        return $paths;
    }

    private static function root(): string
    {
        return str_replace('\\', '/', dirname(__DIR__, 3));
    }
}
