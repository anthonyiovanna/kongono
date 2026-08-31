<?php

declare(strict_types=1);

namespace Kongono\ExtensionHost\Tests\Unit;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use SplFileInfo;

final class PackageBoundaryTest extends TestCase
{
    public function testExactInventoryDependenciesStatusesAndProhibitedSurface(): void
    {
        $package = dirname(__DIR__, 2);
        self::assertSame([
            'Contract/ApplicationExtensionInterface.php',
            'Contract/OperationExtensionInterface.php',
            'Diagnostic/ExtensionHostDiagnostic.php',
            'Exception/ExceptionInterface.php',
            'Exception/ExtensionHostException.php',
            'Exception/ExtensionHostLifecycleException.php',
            'Exception/ExtensionHostUnavailableException.php',
            'Exception/InvalidGeneratedExtensionHostException.php',
            'Internal/LifecycleFailureComposer.php',
            'Internal/PathGuard.php',
            'Runtime/ExtensionHostLoader.php',
            'Runtime/ExtensionHostState.php',
            'Runtime/LoadedExtensionWiring.php',
            'Runtime/TrustedExtensionHost.php',
            'Runtime/TrustedExtensionOperation.php',
        ], self::phpFiles($package . '/src'));
        self::assertCount(9, self::phpFiles($package . '/tests'));

        $manifest = json_decode((string) file_get_contents($package . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($manifest);
        self::assertSame('kongono/extension-host', $manifest['name']);
        self::assertSame([
            'kongono/api-metadata' => '*',
            'kongono/diagnostics' => '*',
            'kongono/extension-composition' => '*',
            'php' => '>=8.5.0 <8.6.0',
        ], $manifest['require']);

        $public = ['Kongono\\ExtensionHost\\Exception\\ExceptionInterface'];
        $internal = [
            'Kongono\\ExtensionHost\\Diagnostic\\ExtensionHostDiagnostic',
            'Kongono\\ExtensionHost\\Internal\\LifecycleFailureComposer',
            'Kongono\\ExtensionHost\\Internal\\PathGuard',
        ];
        $experimental = [
            'Kongono\\ExtensionHost\\Contract\\ApplicationExtensionInterface',
            'Kongono\\ExtensionHost\\Contract\\OperationExtensionInterface',
            'Kongono\\ExtensionHost\\Exception\\ExtensionHostException',
            'Kongono\\ExtensionHost\\Exception\\ExtensionHostLifecycleException',
            'Kongono\\ExtensionHost\\Exception\\ExtensionHostUnavailableException',
            'Kongono\\ExtensionHost\\Exception\\InvalidGeneratedExtensionHostException',
            'Kongono\\ExtensionHost\\Runtime\\ExtensionHostLoader',
            'Kongono\\ExtensionHost\\Runtime\\ExtensionHostState',
            'Kongono\\ExtensionHost\\Runtime\\LoadedExtensionWiring',
            'Kongono\\ExtensionHost\\Runtime\\TrustedExtensionHost',
            'Kongono\\ExtensionHost\\Runtime\\TrustedExtensionOperation',
        ];
        foreach ([ApiStatus::Public->value => $public, ApiStatus::Internal->value => $internal, ApiStatus::Experimental->value => $experimental] as $status => $classes) {
            foreach ($classes as $class) {
                $attributes = (new ReflectionClass($class))->getAttributes(Api::class);
                self::assertCount(1, $attributes);
                self::assertSame($status, $attributes[0]->newInstance()->status->value);
            }
        }

        $combined = '';
        foreach (self::phpFiles($package . '/src') as $relative) {
            $combined .= file_get_contents($package . '/src/' . $relative);
        }
        foreach (['Kongono\\ApplicationGraph', 'Kongono\\Composition', 'Security\\Permit', 'Principal', 'register_shutdown_function', '__destruct'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $combined);
        }
    }

    /** @return list<string> */
    private static function phpFiles(string $directory): array
    {
        $files = [];
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS)) as $file) {
            if ($file instanceof SplFileInfo && $file->isFile() && 'php' === $file->getExtension()) {
                $files[] = str_replace('\\', '/', substr($file->getPathname(), strlen($directory) + 1));
            }
        }
        sort($files, SORT_STRING);

        return $files;
    }
}
