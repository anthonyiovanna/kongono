<?php

declare(strict_types=1);

namespace Kongono\ViewApplication\Tests\Architecture;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use SplFileInfo;

final class ViewApplicationBoundaryTest extends TestCase
{
    public function testPackageManifestHasOnlyAuthorizedDependencies(): void
    {
        $manifest = json_decode((string) file_get_contents(__DIR__ . '/../../composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($manifest);
        self::assertSame('kongono/view-application', $manifest['name']);
        $requirements = $manifest['require'] ?? null;
        $developmentRequirements = $manifest['require-dev'] ?? null;
        $autoload = $manifest['autoload'] ?? null;
        self::assertIsArray($requirements);
        self::assertIsArray($developmentRequirements);
        self::assertIsArray($autoload);
        self::assertSame(['php', 'kongono/api-metadata', 'kongono/diagnostics', 'kongono/view-build', 'kongono/view-contract', 'kongono/view-http', 'kongono/view-runtime', 'psr/http-factory', 'psr/http-message'], array_keys($requirements));
        self::assertSame(['kongono/http-message', 'phpunit/phpunit'], array_keys($developmentRequirements));
        self::assertSame(['Kongono\\ViewApplication\\' => 'src/'], $autoload['psr-4']);
    }

    public function testProductionDeclarationInventoryAndApiStatusesAreExact(): void
    {
        $paths = glob(__DIR__ . '/../../src/{*,*/*}.php', GLOB_BRACE);
        self::assertIsArray($paths);
        self::assertCount(20, $paths);
        foreach ($paths as $path) {
            $relative = str_replace('\\', '/', substr($path, strlen(__DIR__ . '/../../src/'), -4));
            $class = 'Kongono\\ViewApplication\\' . str_replace('/', '\\', $relative);
            if (!class_exists($class) && !interface_exists($class) && !enum_exists($class)) {
                self::fail('Production declaration must be autoloadable.');
            }
            $reflection = new ReflectionClass($class);
            $attributes = $reflection->getAttributes(Api::class);
            self::assertCount(1, $attributes, $class);
            $expected = 'Exception/ExceptionInterface' === $relative
                ? ApiStatus::Public
                : (str_starts_with($relative, 'Internal/') || str_starts_with($relative, 'Diagnostic/') || str_starts_with($relative, 'Exception/') ? ApiStatus::Internal : ApiStatus::Experimental);
            self::assertSame($expected, $attributes[0]->newInstance()->status, $class);
        }
    }

    public function testProductionSurfaceContainsNoUnauthorizedCapability(): void
    {
        $source = '';
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(__DIR__ . '/../../src', \FilesystemIterator::SKIP_DOTS)) as $entry) {
            if (!$entry instanceof SplFileInfo) {
                self::fail('Production entry must be a file descriptor.');
            }
            if ($entry->isFile() && 'php' === $entry->getExtension()) {
                $source .= (string) file_get_contents($entry->getPathname());
            }
        }
        foreach (['getenv(', 'putenv(', '$_SERVER', '$_ENV', '$_SESSION', 'session_start(', 'setcookie(', 'curl_', 'PDO', 'eval(', 'glob(', 'ReflectionClass', 'RequestHandlerInterface', 'ServerRequestInterface'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source, $forbidden);
        }
    }

    public function testPlanningSourceAdmissionAndCurrentChecksDoNotDiscoverOrExecute(): void
    {
        $pathGuard = (string) file_get_contents(__DIR__ . '/../../src/Internal/ViewApplicationPathGuard.php');
        foreach (['scandir(', 'glob(', 'DirectoryIterator', 'RecursiveIterator', 'getenv(', '$_ENV', '$_SERVER'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $pathGuard, $forbidden);
        }

        $workflow = (string) file_get_contents(__DIR__ . '/../../src/Workflow/ViewApplicationBuildWorkflow.php');
        $forbiddenTokens = [T_INCLUDE, T_INCLUDE_ONCE, T_REQUIRE, T_REQUIRE_ONCE, T_EVAL];
        foreach (token_get_all($workflow) as $token) {
            if (is_array($token)) {
                self::assertNotContains($token[0], $forbiddenTokens);
            }
        }
        foreach (['glob(', 'DirectoryIterator', 'RecursiveIterator', 'latest', 'newest', 'current.json', 'current.php'] as $forbidden) {
            self::assertStringNotContainsStringIgnoringCase($forbidden, $workflow, $forbidden);
        }
    }

    public function testReverseProductionConsumersAreExact(): void
    {
        $consumers = [];
        $manifests = glob(dirname(__DIR__, 4) . '/packages/*/composer.json');
        self::assertIsArray($manifests);
        foreach ($manifests as $path) {
            $manifest = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
            self::assertIsArray($manifest);
            $requirements = $manifest['require'] ?? null;
            self::assertIsArray($requirements);
            if (isset($requirements['kongono/view-application'])) {
                $name = $manifest['name'] ?? null;
                self::assertIsString($name);
                $consumers[] = $name;
            }
        }
        sort($consumers, SORT_STRING);
        self::assertSame(['kongono/console-application', 'kongono/view-inspection'], $consumers);
    }
}
