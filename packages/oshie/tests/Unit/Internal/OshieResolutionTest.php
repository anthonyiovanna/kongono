<?php

declare(strict_types=1);

namespace Kongono\Oshie\Tests\Unit\Internal;

use PHPUnit\Framework\TestCase;

final class OshieResolutionTest extends TestCase
{
    public function testProductionBoundaryContainsNoProhibitedAuthorityOrExecutableExampleClaim(): void
    {
        $root = dirname(__DIR__, 3) . '/src';
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));
        $source = '';
        foreach ($files as $file) {
            if ($file instanceof \SplFileInfo && $file->isFile() && 'php' === $file->getExtension()) {
                $source .= (string) file_get_contents($file->getPathname());
            }
        }
        foreach (['proc_open(', 'shell_exec(', 'exec(', 'eval(', 'curl_', 'Kongono\\Chie', 'Kongono\\Hantei', 'git ', 'provider', 'model'] as $prohibited) {
            self::assertStringNotContainsString($prohibited, $source);
        }
        self::assertStringNotContainsString('executable example', strtolower($source));
    }
}
