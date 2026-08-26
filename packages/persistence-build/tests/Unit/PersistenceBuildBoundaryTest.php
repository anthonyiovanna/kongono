<?php

declare(strict_types=1);

namespace Kongono\PersistenceBuild\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SplFileInfo;

final class PersistenceBuildBoundaryTest extends TestCase
{
    public function testProductionBoundaryContainsNoRuntimeOrmOrAmbientAuthority(): void
    {
        $root = dirname(__DIR__, 2);
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root . '/src', \FilesystemIterator::SKIP_DOTS));
        $count = 0;
        foreach ($files as $file) {
            if (!$file instanceof SplFileInfo) {
                continue;
            }
            if (!$file->isFile() || 'php' !== $file->getExtension()) {
                continue;
            }
            ++$count;
            $source = (string) file_get_contents($file->getPathname());
            self::assertStringContainsString('declare(strict_types=1);', $source);
            self::assertSame(0, preg_match('/\$_(?:ENV|SERVER|GET|POST)|\b(?:getenv|putenv|eval|mysqli_connect|pg_connect|PDO)\s*\(|\b(?:EntityManager|UnitOfWork|IdentityMap|LazyProxy|ActiveRecord)\b/i', $source));
            self::assertStringNotContainsString('Kongono\\DependencyInjection', $source);
        }
        self::assertSame(52, $count);
    }
}
