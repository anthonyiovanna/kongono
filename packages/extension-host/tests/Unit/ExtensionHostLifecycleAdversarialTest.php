<?php

declare(strict_types=1);

namespace Kongono\ExtensionHost\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class ExtensionHostLifecycleAdversarialTest extends TestCase
{
    public function testLifecycleSourceHasNoDestructorShutdownHookAmbientRegistryOrPostLoadReflection(): void
    {
        $root = dirname(__DIR__, 2) . '/src';
        $lifecycle = '';
        foreach (['Runtime/LoadedExtensionWiring.php', 'Runtime/TrustedExtensionHost.php', 'Runtime/TrustedExtensionOperation.php'] as $relative) {
            $lifecycle .= (string) file_get_contents($root . '/' . $relative);
        }
        foreach (['__destruct', 'register_shutdown_function', 'WeakReference', 'FiberLocal', 'ThreadLocal', '$GLOBALS', 'Reflection', 'call_user_func', 'eval('] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $lifecycle);
        }
        self::assertSame(1, substr_count((string) file_get_contents($root . '/Runtime/ExtensionHostLoader.php'), 'require $snapshot'));
    }

    public function testTrustedInProcessBoundaryNeverClaimsSandboxing(): void
    {
        $readme = (string) file_get_contents(dirname(__DIR__, 2) . '/README.md');
        self::assertStringContainsString('is not sandboxed', $readme);
        self::assertStringContainsString('full technical power of its process', $readme);
    }
}
