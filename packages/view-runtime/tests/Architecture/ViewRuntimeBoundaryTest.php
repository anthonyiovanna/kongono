<?php

declare(strict_types=1);

namespace Kongono\ViewRuntime\Tests\Architecture;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use SplFileInfo;

final class ViewRuntimeBoundaryTest extends TestCase
{
    private const array PUBLIC_TYPES = ['Kongono\\ViewRuntime\\Exception\\ExceptionInterface'];
    private const array INTERNAL_TYPES = [
        'Kongono\\ViewRuntime\\Diagnostic\\ViewRuntimeDiagnostic',
        'Kongono\\ViewRuntime\\Exception\\ViewRuntimeException',
        'Kongono\\ViewRuntime\\Internal\\BufferedRenderSink',
        'Kongono\\ViewRuntime\\Internal\\ContextualEscaper',
        'Kongono\\ViewRuntime\\Internal\\GeneratedProgramLoader',
        'Kongono\\ViewRuntime\\Internal\\RenderOperation',
        'Kongono\\ViewRuntime\\Internal\\RuntimeGuard',
        'Kongono\\ViewRuntime\\Internal\\StableArtifactReader',
    ];

    public function testManifestInventoryDependenciesAndStatusesAreExact(): void
    {
        $package = dirname(__DIR__, 2);
        $root = dirname($package, 2);
        $manifest = json_decode((string) file_get_contents($package . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame([
            'name' => 'kongono/view-runtime',
            'description' => 'Exact-current compiled view loading and synchronous bounded rendering for Kongono Framework.',
            'type' => 'library',
            'require' => ['php' => '>=8.5.0 <8.6.0', 'kongono/api-metadata' => '*', 'kongono/diagnostics' => '*', 'kongono/view-build' => '*', 'kongono/view-contract' => '*'],
            'require-dev' => ['phpunit/phpunit' => '^13.0'],
            'autoload' => ['psr-4' => ['Kongono\\ViewRuntime\\' => 'src/']],
            'autoload-dev' => ['psr-4' => ['Kongono\\ViewRuntime\\Tests\\' => 'tests/']],
        ], $manifest);
        self::assertCount(19, self::phpFiles($package . '/src'));
        self::assertCount(11, self::phpFiles($package . '/tests'));
        $counts = ['experimental' => 0, 'public' => 0, 'internal' => 0];
        foreach (self::phpFiles($package . '/src') as $path) {
            $relative = str_replace('\\', '/', substr($path, strlen($package . '/src') + 1));
            $type = 'Kongono\\ViewRuntime\\' . str_replace(['/', '.php'], ['\\', ''], $relative);
            self::assertTrue(class_exists($type) || interface_exists($type) || enum_exists($type), $type);
            $reflection = new ReflectionClass($type);
            $attributes = $reflection->getAttributes(Api::class);
            self::assertCount(1, $attributes, $type);
            $status = $attributes[0]->newInstance()->status;
            $expected = in_array($type, self::PUBLIC_TYPES, true) ? ApiStatus::Public : (in_array($type, self::INTERNAL_TYPES, true) ? ApiStatus::Internal : ApiStatus::Experimental);
            self::assertSame($expected, $status, $type);
            ++$counts[$status->value];
        }
        self::assertSame(['experimental' => 10, 'public' => 1, 'internal' => 8], $counts);

        $manifests = glob($root . '/packages/*/composer.json');
        self::assertIsArray($manifests);
        self::assertCount(116, $manifests);
        $edges = 0;
        foreach ($manifests as $path) {
            $candidate = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
            self::assertIsArray($candidate);
            $requirements = $candidate['require'] ?? null;
            self::assertIsArray($requirements);
            foreach (array_keys($requirements) as $dependency) {
                $edges += is_string($dependency) && str_starts_with($dependency, 'kongono/') ? 1 : 0;
            }
            if (!in_array($candidate['name'] ?? null, ['kongono/view-application', 'kongono/view-runtime'], true)) {
                self::assertArrayNotHasKey('kongono/view-runtime', $requirements);
            }
        }
        self::assertSame(501, $edges);
    }

    public function testProductionSurfaceContainsNoProhibitedAuthority(): void
    {
        $source = implode("\n", array_map(static fn(string $path): string => (string) file_get_contents($path), self::phpFiles(dirname(__DIR__, 2) . '/src')));
        foreach (['scandir(', 'glob(', 'RecursiveDirectory', 'CompiledViewArtifactBuilder', 'CompiledViewArtifactValidator', 'ViewSourceParser', 'GeneratedPhpEmitter', 'eval(', 'ob_start(', 'file_put_contents(', 'getenv(', 'putenv(', '$_ENV', '$_SERVER', '$GLOBALS', 'curl_exec(', 'PDO', 'session_start(', 'header('] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source, $forbidden);
        }
        self::assertSame(1, substr_count($source, 'require $path'));
    }

    /** @return list<string> */
    private static function phpFiles(string $root): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));
        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->isFile() && 'php' === $file->getExtension()) {
                $files[] = $file->getPathname();
            }
        }
        sort($files);

        return $files;
    }
}
