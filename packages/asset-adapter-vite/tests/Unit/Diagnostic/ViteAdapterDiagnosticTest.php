<?php

declare(strict_types=1);

namespace Kongono\AssetAdapterVite\Tests\Unit\Diagnostic;

use Kongono\AssetAdapterVite\Diagnostic\ViteAdapterDiagnostic;
use PHPUnit\Framework\TestCase;

final class ViteAdapterDiagnosticTest extends TestCase
{
    public function testExactIds(): void
    {
        self::assertSame(['KGN-ASSET-VITE-1926','KGN-ASSET-VITE-1927','KGN-ASSET-VITE-1928','KGN-ASSET-VITE-1929','KGN-ASSET-VITE-1930'], array_column(ViteAdapterDiagnostic::cases(), 'value'));
    }
}
