<?php

declare(strict_types=1);

namespace Kongono\SecurityApplication\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class SecurityApplicationBoundaryTest extends TestCase
{
    public function testManifestAndProductionSurfaceAreExact(): void
    {
        $root = dirname(__DIR__, 2);
        $manifest = json_decode((string) file_get_contents($root . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($manifest);
        self::assertSame('kongono/security-application', $manifest['name']);
        self::assertSame([
            'kongono/api-metadata' => '*',
            'kongono/authorization' => '*',
            'kongono/diagnostics' => '*',
            'kongono/security-enforcement' => '*',
            'kongono/security-http' => '*',
            'kongono/security-policy-build' => '*',
            'php' => '>=8.5.0 <8.6.0',
            'psr/http-message' => '^2.0',
        ], $manifest['require']);
        $sourceFiles = glob($root . '/src/*.php');
        self::assertIsArray($sourceFiles);
        self::assertSame(['BrowserSessionProtectedApplication.php'], array_map('basename', $sourceFiles));
    }
}
