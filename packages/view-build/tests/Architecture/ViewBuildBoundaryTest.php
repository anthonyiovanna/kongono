<?php

declare(strict_types=1);

namespace Kongono\ViewBuild\Tests\Architecture;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use SplFileInfo;

final class ViewBuildBoundaryTest extends TestCase
{
    private const array PUBLIC_TYPES = ['Kongono\\ViewBuild\\Exception\\ExceptionInterface'];
    private const array INTERNAL_TYPES = [
        'Kongono\\ViewBuild\\Model\\CompiledViewModel',
        'Kongono\\ViewBuild\\Model\\CompiledViewDefinition',
        'Kongono\\ViewBuild\\Model\\SourceMapEntry',
        'Kongono\\ViewBuild\\Generated\\RenderSink',
        'Kongono\\ViewBuild\\Generated\\GeneratedPhpEmitter',
        'Kongono\\ViewBuild\\Diagnostic\\ViewBuildDiagnostic',
        'Kongono\\ViewBuild\\Exception\\ViewBuildException',
        'Kongono\\ViewBuild\\Internal\\CanonicalJson',
        'Kongono\\ViewBuild\\Internal\\StrictJsonDecoder',
        'Kongono\\ViewBuild\\Internal\\PathGuard',
        'Kongono\\ViewBuild\\Internal\\BuildGuard',
    ];

    public function testManifestInventoryAndDependencyBoundaryAreExact(): void
    {
        $package = dirname(__DIR__, 2);
        $root = dirname($package, 2);
        $manifest = json_decode((string) file_get_contents($package . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame([
            'name' => 'kongono/view-build',
            'description' => 'Deterministic structured view-source compilation and content-addressed artifact publication for Kongono Framework.',
            'type' => 'library',
            'require' => ['php' => '>=8.5.0 <8.6.0', 'kongono/api-metadata' => '*', 'kongono/diagnostics' => '*', 'kongono/view-contract' => '*'],
            'require-dev' => ['phpunit/phpunit' => '^13.0'],
            'autoload' => ['psr-4' => ['Kongono\\ViewBuild\\' => 'src/']],
            'autoload-dev' => ['psr-4' => ['Kongono\\ViewBuild\\Tests\\' => 'tests/']],
        ], $manifest);
        self::assertCount(30, self::phpFiles($package . '/src'));
        self::assertCount(16, self::phpFiles($package . '/tests'));
        $manifests = glob($root . '/packages/*/composer.json');
        self::assertIsArray($manifests);
        self::assertCount(102, $manifests);
        foreach ($manifests as $path) {
            $candidate = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
            self::assertIsArray($candidate);
            $requirements = $candidate['require'] ?? null;
            self::assertIsArray($requirements);
            if (in_array($candidate['name'] ?? null, ['kongono/view-application', 'kongono/view-inspection', 'kongono/view-runtime'], true)) {
                self::assertSame('*', $requirements['kongono/view-build'] ?? null);

                continue;
            }
            if ('kongono/view-build' !== ($candidate['name'] ?? null)) {
                self::assertArrayNotHasKey('kongono/view-build', $requirements);
            }
        }
    }

    public function testEveryProductionFileHasOneExactStatusedDeclaration(): void
    {
        $counts = ['experimental' => 0, 'public' => 0, 'internal' => 0];
        foreach (self::phpFiles(dirname(__DIR__, 2) . '/src') as $path) {
            $source = (string) file_get_contents($path);
            self::assertSame(1, preg_match_all('/#\[Api\(ApiStatus::(?:Experimental|Internal|Public)\)\]/', $source));
            self::assertSame(1, preg_match_all('/\b(?:final readonly class|final class|abstract class|interface|enum)\s+[A-Za-z_][A-Za-z0-9_]*/', $source));
            if (1 !== preg_match('/namespace\s+([^;]+);/', $source, $namespace)) {
                self::fail('Expected one exact namespace declaration.');
            }
            $type = $namespace[1] . '\\' . pathinfo($path, PATHINFO_FILENAME);
            if (!class_exists($type) && !interface_exists($type) && !enum_exists($type)) {
                self::fail('Expected discovered production type to exist.');
            }
            $reflection = new ReflectionClass($type);
            $status = $reflection->getAttributes(Api::class)[0]->newInstance()->status;
            $expected = in_array($type, self::PUBLIC_TYPES, true) ? ApiStatus::Public : (in_array($type, self::INTERNAL_TYPES, true) ? ApiStatus::Internal : ApiStatus::Experimental);
            self::assertSame($expected, $status, $type);
            ++$counts[$status->value];
        }
        self::assertSame(['experimental' => 18, 'public' => 1, 'internal' => 11], $counts);
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
