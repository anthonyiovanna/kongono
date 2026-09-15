<?php

declare(strict_types=1);

namespace Kongono\AssetBuild\Tests\Unit\Diagnostic;

use Kongono\AssetBuild\Diagnostic\AssetBuildDiagnostic;
use PHPUnit\Framework\TestCase;

final class AssetBuildDiagnosticTest extends TestCase
{
    public function testDiagnosticIdentifiersAndGuidanceAreComplete(): void
    {
        self::assertSame(['KGN-ASSET-BUILD-1911', 'KGN-ASSET-BUILD-1912', 'KGN-ASSET-BUILD-1913', 'KGN-ASSET-BUILD-1914', 'KGN-ASSET-BUILD-1915', 'KGN-ASSET-BUILD-1916'], array_map(static fn($value): string => $value->value, AssetBuildDiagnostic::cases()));
        foreach (AssetBuildDiagnostic::cases() as $diagnostic) {
            self::assertNotSame('', $diagnostic->title());
            self::assertNotSame('', $diagnostic->remediation());
        }
    }
}
