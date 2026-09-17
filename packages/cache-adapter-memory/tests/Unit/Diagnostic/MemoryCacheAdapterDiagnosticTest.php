<?php

declare(strict_types=1);

namespace Kongono\CacheAdapterMemory\Tests\Unit\Diagnostic;

use Kongono\CacheAdapterMemory\Diagnostic\MemoryCacheAdapterDiagnostic;
use PHPUnit\Framework\TestCase;

final class MemoryCacheAdapterDiagnosticTest extends TestCase
{
    public function testDiagnosticCatalogIsClosedAndSafe(): void
    {
        self::assertSame([
            'KGN-CACHE-ADAPTER-MEMORY-2021',
            'KGN-CACHE-ADAPTER-MEMORY-2022',
            'KGN-CACHE-ADAPTER-MEMORY-2023',
        ], array_column(MemoryCacheAdapterDiagnostic::cases(), 'value'));
        self::assertSame('entry_count_range', MemoryCacheAdapterDiagnostic::InvalidCapacity->context(['violation' => 'entry_count_range'])->fields()[0]->value());
    }
}
