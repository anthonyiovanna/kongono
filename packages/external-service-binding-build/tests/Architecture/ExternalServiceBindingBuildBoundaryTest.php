<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceBindingBuild\Tests\Architecture;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class ExternalServiceBindingBuildBoundaryTest extends TestCase
{
    public function testExactInventoriesManifestAndApiStatuses(): void
    {
        $package = dirname(__DIR__, 2);
        self::assertSame([
            'Artifact/ExternalServiceBindingArtifact.php', 'Artifact/ExternalServiceBindingArtifactBuilder.php',
            'Artifact/ExternalServiceBindingArtifactPublisher.php', 'Artifact/ExternalServiceBindingArtifactValidator.php',
            'Diagnostic/ExternalServiceBindingBuildDiagnostic.php', 'Exception/ExceptionInterface.php',
            'Exception/ExternalServiceBindingArtifactPublicationException.php', 'Exception/ExternalServiceBindingBuildException.php',
            'Exception/GeneratedExternalServiceBindingsPublicationException.php', 'Exception/InvalidExternalServiceBindingArtifactException.php',
            'Exception/InvalidExternalServiceBindingSourceException.php', 'Exception/InvalidGeneratedExternalServiceBindingsException.php',
            'Exception/StaleExternalServiceBindingArtifactException.php', 'Exception/StaleGeneratedExternalServiceBindingsException.php',
            'Generated/GeneratedExternalServiceBindings.php', 'Generated/GeneratedExternalServiceBindingsGenerator.php',
            'Generated/GeneratedExternalServiceBindingsPublisher.php', 'Generated/GeneratedExternalServiceBindingsValidator.php',
            'Internal/BindingArtifactCodec.php', 'Internal/CanonicalJson.php', 'Internal/GeneratedBindingsEmitter.php',
            'Internal/GeneratedBindingsImplementation.php', 'Internal/PathGuard.php', 'Internal/StrictJsonDecoder.php',
            'Source/ExternalServiceBindingSource.php', 'Source/ExternalServiceBindingSourceIdentity.php',
            'Version/ExternalServiceBindingArtifactVersion.php',
        ], self::phpFiles($package . '/src'));
        self::assertSame([
            'Architecture/ExternalServiceBindingBuildBoundaryTest.php', 'Security/ExternalServiceBindingBuildDisclosureTest.php',
            'Support/ExternalServiceBindingSourceFactory.php', 'Unit/Artifact/ExternalServiceBindingArtifactBuilderTest.php',
            'Unit/Artifact/ExternalServiceBindingArtifactPublisherTest.php', 'Unit/Artifact/ExternalServiceBindingArtifactTest.php',
            'Unit/Artifact/ExternalServiceBindingArtifactValidatorTest.php', 'Unit/Diagnostic/ExternalServiceBindingBuildDiagnosticTest.php',
            'Unit/Exception/ExternalServiceBindingBuildExceptionTest.php', 'Unit/Generated/GeneratedExternalServiceBindingsGeneratorTest.php',
            'Unit/Generated/GeneratedExternalServiceBindingsPublisherTest.php', 'Unit/Generated/GeneratedExternalServiceBindingsTest.php',
            'Unit/Generated/GeneratedExternalServiceBindingsValidatorTest.php', 'Unit/Source/ExternalServiceBindingSourceIdentityTest.php',
            'Unit/Source/ExternalServiceBindingSourceTest.php',
        ], self::phpFiles($package . '/tests'));
        $manifest = json_decode((string) file_get_contents($package . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($manifest);
        self::assertArrayHasKey('require', $manifest);
        self::assertIsArray($manifest['require']);
        self::assertSame(['kongono/api-metadata', 'kongono/diagnostics', 'kongono/external-service-contract', 'kongono/network-egress-contract'], array_values(array_filter(array_keys($manifest['require']), static fn(int|string $name): bool => is_string($name) && str_starts_with($name, 'kongono/'))));

        $internal = [
            'Kongono\\ExternalServiceBindingBuild\\Diagnostic\\ExternalServiceBindingBuildDiagnostic',
            'Kongono\\ExternalServiceBindingBuild\\Exception\\ExternalServiceBindingBuildException',
            'Kongono\\ExternalServiceBindingBuild\\Internal\\BindingArtifactCodec',
            'Kongono\\ExternalServiceBindingBuild\\Internal\\CanonicalJson',
            'Kongono\\ExternalServiceBindingBuild\\Internal\\GeneratedBindingsEmitter',
            'Kongono\\ExternalServiceBindingBuild\\Internal\\GeneratedBindingsImplementation',
            'Kongono\\ExternalServiceBindingBuild\\Internal\\PathGuard',
            'Kongono\\ExternalServiceBindingBuild\\Internal\\StrictJsonDecoder',
        ];
        foreach (self::declarations($package . '/src') as $class) {
            $expected = 'Kongono\\ExternalServiceBindingBuild\\Exception\\ExceptionInterface' === $class ? ApiStatus::Public : (in_array($class, $internal, true) ? ApiStatus::Internal : ApiStatus::Experimental);
            $attributes = (new ReflectionClass($class))->getAttributes(Api::class);
            self::assertCount(1, $attributes);
            self::assertSame($expected, $attributes[0]->newInstance()->status, $class);
        }
    }

    public function testTopologyAndAggregateInventoriesAreExactAndAcyclic(): void
    {
        $root = dirname(__DIR__, 4);
        $manifests = glob($root . '/packages/*/composer.json');
        self::assertIsArray($manifests);
        self::assertCount(89, $manifests);
        $names = [];
        foreach ($manifests as $path) {
            $manifest = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
            self::assertIsArray($manifest);
            self::assertIsString($manifest['name']);
            $names[$manifest['name']] = true;
        }
        $graph = [];
        $edges = 0;
        foreach ($manifests as $path) {
            $manifest = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
            self::assertIsArray($manifest);
            self::assertIsString($manifest['name']);
            self::assertIsArray($manifest['require']);
            $graph[$manifest['name']] = [];
            foreach (array_keys($manifest['require']) as $dependency) {
                if (isset($names[$dependency])) {
                    $graph[$manifest['name']][] = $dependency;
                    ++$edges;
                }
            }
            if (!in_array($manifest['name'], ['kongono/external-service-application', 'kongono/external-service-binding-build', 'kongono/external-service-inspection'], true)) {
                self::assertArrayNotHasKey('kongono/external-service-binding-build', $manifest['require']);
            }
        }
        self::assertSame(375, $edges);
        $visit = static function (string $name, array $path = []) use (&$visit, $graph): void {
            self::assertArrayNotHasKey($name, $path);
            $path[$name] = true;
            foreach ($graph[$name] ?? [] as $dependency) {
                $visit($dependency, $path);
            }
        };
        foreach (array_keys($graph) as $name) {
            $visit($name);
        }
        self::assertCount(1726, array_filter(self::allPhp($root . '/packages'), static fn(string $path): bool => str_contains(str_replace('\\', '/', $path), '/src/')));
        self::assertCount(894, array_filter(self::allPhp($root . '/packages'), static fn(string $path): bool => str_contains(str_replace('\\', '/', $path), '/tests/')));
        self::assertCount(92, self::allPhp($root . '/tests'));
    }

    /** @return list<string> */
    private static function phpFiles(string $directory): array
    {
        $files = [];
        foreach (self::allPhp($directory) as $path) {
            $files[] = str_replace('\\', '/', substr($path, strlen($directory) + 1));
        }
        sort($files, SORT_STRING);

        return $files;
    }

    /** @return list<string> */
    private static function allPhp(string $directory): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            self::assertInstanceOf(\SplFileInfo::class, $file);
            if ($file->isFile() && 'php' === $file->getExtension()) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    /** @return list<class-string> */
    private static function declarations(string $directory): array
    {
        $classes = [];
        foreach (self::phpFiles($directory) as $relative) {
            $class = 'Kongono\\ExternalServiceBindingBuild\\' . str_replace(['/', '.php'], ['\\', ''], $relative);
            if (!class_exists($class) && !interface_exists($class) && !enum_exists($class)) {
                self::fail('Expected declaration for ' . $relative);
            }
            $classes[] = $class;
        }

        return $classes;
    }
}
