<?php

declare(strict_types=1);

namespace Kongono\ExtensionBuild\Tests\Architecture;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use SplFileInfo;

final class ExtensionBuildBoundaryTest extends TestCase
{
    public function testExactInventoriesDependenciesTopologyAndRootIntegration(): void
    {
        $root = dirname(__DIR__, 4);
        $package = $root . '/packages/extension-build';
        self::assertSame([
            'Artifact/ExtensionDeclarationArtifact.php',
            'Artifact/ExtensionDeclarationArtifactBuilder.php',
            'Artifact/ExtensionDeclarationArtifactPublisher.php',
            'Artifact/ExtensionDeclarationArtifactValidator.php',
            'ArtifactVersion.php',
            'Diagnostic/ExtensionBuildDiagnostic.php',
            'Exception/ExceptionInterface.php',
            'Exception/ExtensionBuildException.php',
            'Exception/ExtensionDeclarationPublicationException.php',
            'Exception/InvalidExtensionBuildException.php',
            'Exception/StaleExtensionDeclarationArtifactException.php',
            'Identity/DeclarationSetIdentity.php',
            'Internal/CanonicalJson.php',
            'Internal/DeclarationArtifactCodec.php',
            'Internal/PathGuard.php',
        ], self::phpFiles($package . '/src'));
        self::assertSame([
            'Architecture/ExtensionBuildBoundaryTest.php',
            'Unit/Artifact/ExtensionDeclarationArtifactBuilderTest.php',
            'Unit/Artifact/ExtensionDeclarationArtifactCurrentnessTest.php',
            'Unit/Artifact/ExtensionDeclarationArtifactPublicationTest.php',
            'Unit/Artifact/ExtensionDeclarationArtifactValidationTest.php',
            'Unit/ArtifactVersionTest.php',
            'Unit/Diagnostic/ExtensionBuildDiagnosticTest.php',
            'Unit/Exception/ExtensionBuildExceptionTest.php',
            'Unit/Identity/DeclarationSetIdentityTest.php',
        ], self::phpFiles($package . '/tests'));

        /** @var array<string, mixed> $manifest */
        $manifest = json_decode((string) file_get_contents($package . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame([
            'name' => 'kongono/extension-build',
            'description' => 'Deterministic declaration artifact build and publication for Kongono extensions.',
            'type' => 'library',
            'require' => [
                'kongono/api-metadata' => '*',
                'kongono/diagnostics' => '*',
                'kongono/extension-contract' => '*',
                'php' => '>=8.5.0 <8.6.0',
            ],
            'require-dev' => ['phpunit/phpunit' => '^13.0'],
            'autoload' => ['psr-4' => ['Kongono\\ExtensionBuild\\' => 'src/']],
            'autoload-dev' => ['psr-4' => ['Kongono\\ExtensionBuild\\Tests\\' => 'tests/']],
        ], $manifest);

        /** @var array<string, mixed> $rootManifest */
        $rootManifest = json_decode((string) file_get_contents($root . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        $rootRequire = $rootManifest['require'] ?? null;
        self::assertIsArray($rootRequire);
        self::assertSame('@dev', $rootRequire['kongono/extension-build']);
        $rootAutoloadDev = $rootManifest['autoload-dev'] ?? null;
        self::assertIsArray($rootAutoloadDev);
        $rootAutoloadDevPsr4 = $rootAutoloadDev['psr-4'] ?? null;
        self::assertIsArray($rootAutoloadDevPsr4);
        self::assertSame('packages/extension-build/tests/', $rootAutoloadDevPsr4['Kongono\\ExtensionBuild\\Tests\\']);
        $validation = 'composer --working-dir=packages/extension-build validate --no-check-publish';
        $rootScripts = $rootManifest['scripts'] ?? null;
        self::assertIsArray($rootScripts);
        $packageValidation = $rootScripts['validate:packages'] ?? null;
        self::assertIsArray($packageValidation);
        self::assertSame(1, count(array_keys($packageValidation, $validation, true)));
        foreach (['.php-cs-fixer.dist.php', 'phpstan.neon.dist', 'phpunit.xml.dist'] as $configuration) {
            $contents = (string) file_get_contents($root . '/' . $configuration);
            self::assertSame(1, substr_count($contents, 'packages/extension-build/src'));
            self::assertSame(1, substr_count($contents, 'packages/extension-build/tests'));
        }

        $manifests = glob($root . '/packages/*/composer.json');
        self::assertIsArray($manifests);
        self::assertCount(81, $manifests);
        /** @var array<string, list<string>> $graph */
        $graph = [];
        $edges = 0;
        foreach ($manifests as $path) {
            /** @var array<string, mixed> $candidate */
            $candidate = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
            $name = $candidate['name'] ?? null;
            self::assertIsString($name);
            $require = $candidate['require'] ?? null;
            self::assertIsArray($require);
            $graph[$name] = [];
            foreach ($require as $dependency => $_constraint) {
                self::assertIsString($dependency);
                if (str_starts_with($dependency, 'kongono/')) {
                    $graph[$name][] = $dependency;
                    ++$edges;
                }
            }
            if (!in_array($name, ['kongono/extension-admission', 'kongono/extension-build', 'kongono/extension-composition', 'kongono/extension-host', 'kongono/extension-inspection'], true)) {
                self::assertArrayNotHasKey('kongono/extension-build', $require);
            }
        }
        self::assertSame(317, $edges);
        $visit = static function (string $packageName, array $path = []) use (&$visit, $graph): void {
            self::assertArrayNotHasKey($packageName, $path);
            $path[$packageName] = true;
            foreach ($graph[$packageName] ?? [] as $dependency) {
                if (isset($graph[$dependency])) {
                    $visit($dependency, $path);
                }
            }
        };
        foreach (array_keys($graph) as $packageName) {
            $visit($packageName);
        }
    }

    public function testApiStatusesAndProhibitedProductionSurfaceAreExact(): void
    {
        $public = ['Kongono\\ExtensionBuild\\Exception\\ExceptionInterface'];
        $experimental = [
            'Kongono\\ExtensionBuild\\ArtifactVersion',
            'Kongono\\ExtensionBuild\\Artifact\\ExtensionDeclarationArtifact',
            'Kongono\\ExtensionBuild\\Artifact\\ExtensionDeclarationArtifactBuilder',
            'Kongono\\ExtensionBuild\\Artifact\\ExtensionDeclarationArtifactPublisher',
            'Kongono\\ExtensionBuild\\Artifact\\ExtensionDeclarationArtifactValidator',
            'Kongono\\ExtensionBuild\\Exception\\ExtensionDeclarationPublicationException',
            'Kongono\\ExtensionBuild\\Exception\\InvalidExtensionBuildException',
            'Kongono\\ExtensionBuild\\Exception\\StaleExtensionDeclarationArtifactException',
            'Kongono\\ExtensionBuild\\Identity\\DeclarationSetIdentity',
        ];
        $internal = [
            'Kongono\\ExtensionBuild\\Diagnostic\\ExtensionBuildDiagnostic',
            'Kongono\\ExtensionBuild\\Exception\\ExtensionBuildException',
            'Kongono\\ExtensionBuild\\Internal\\CanonicalJson',
            'Kongono\\ExtensionBuild\\Internal\\DeclarationArtifactCodec',
            'Kongono\\ExtensionBuild\\Internal\\PathGuard',
        ];
        foreach ([ApiStatus::Public->value => $public, ApiStatus::Experimental->value => $experimental, ApiStatus::Internal->value => $internal] as $expected => $classes) {
            foreach ($classes as $class) {
                $attributes = (new ReflectionClass($class))->getAttributes(Api::class);
                self::assertCount(1, $attributes);
                self::assertSame($expected, $attributes[0]->newInstance()->status->value);
            }
        }

        $source = '';
        foreach (self::phpFiles(dirname(__DIR__, 2) . '/src') as $relative) {
            $source .= file_get_contents(dirname(__DIR__, 2) . '/src/' . $relative);
        }
        foreach (['ApplicationGraph', 'Composition', 'Registry', 'Loader', 'Host', 'callable', 'Closure', 'Reflection', 'getenv(', 'putenv(', 'scandir(', 'glob(', 'serialize(', 'include ', 'require ', 'PDO', 'curl_'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source);
        }
        foreach (['admission', 'grant', 'hosting', 'execution', 'discovery'] as $forbiddenConcept) {
            self::assertDoesNotMatchRegularExpression(sprintf('/\\b%s\\b/i', $forbiddenConcept), $source);
        }
    }

    /** @return list<string> */
    private static function phpFiles(string $directory): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if (!$file instanceof SplFileInfo) {
                continue;
            }
            if ($file->isFile() && 'php' === $file->getExtension()) {
                $files[] = str_replace('\\', '/', substr($file->getPathname(), strlen($directory) + 1));
            }
        }
        sort($files);

        return $files;
    }
}
