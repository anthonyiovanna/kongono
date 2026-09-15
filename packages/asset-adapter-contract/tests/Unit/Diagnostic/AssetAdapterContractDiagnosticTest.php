<?php

declare(strict_types=1);

namespace Kongono\AssetAdapterContract\Tests\Unit\Diagnostic;

use Kongono\AssetAdapterContract\Diagnostic\AssetAdapterContractDiagnostic;
use PHPUnit\Framework\TestCase;

final class AssetAdapterContractDiagnosticTest extends TestCase
{
    public function testExactIds(): void
    {
        self::assertSame(['KGN-ASSET-ADAPTER-1921','KGN-ASSET-ADAPTER-1922','KGN-ASSET-ADAPTER-1923','KGN-ASSET-ADAPTER-1924','KGN-ASSET-ADAPTER-1925'], array_column(AssetAdapterContractDiagnostic::cases(), 'value'));
    }
}
