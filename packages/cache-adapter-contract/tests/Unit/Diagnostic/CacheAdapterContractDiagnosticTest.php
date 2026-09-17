<?php

declare(strict_types=1);

namespace Kongono\CacheAdapterContract\Tests\Unit\Diagnostic;

use Kongono\CacheAdapterContract\Diagnostic\CacheAdapterContractDiagnostic;
use PHPUnit\Framework\TestCase;

final class CacheAdapterContractDiagnosticTest extends TestCase
{
    public function testDiagnosticIdsAndContextsAreClosed(): void
    {
        self::assertSame([
            'KGN-CACHE-ADAPTER-CONTRACT-2011',
            'KGN-CACHE-ADAPTER-CONTRACT-2012',
            'KGN-CACHE-ADAPTER-CONTRACT-2013',
            'KGN-CACHE-ADAPTER-CONTRACT-2014',
            'KGN-CACHE-ADAPTER-CONTRACT-2015',
        ], array_column(CacheAdapterContractDiagnostic::cases(), 'value'));
        self::assertSame('invalid_relation', CacheAdapterContractDiagnostic::InvalidResult->context(['violation' => 'invalid_relation'])->fields()[0]->value());
    }
}
