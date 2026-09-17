<?php

declare(strict_types=1);

namespace Kongono\CacheApplication\Tests\Unit\Diagnostic;

use Kongono\CacheApplication\Diagnostic\CacheApplicationDiagnostic;
use Kongono\CacheApplication\Exception\InvalidCacheApplicationException;
use PHPUnit\Framework\TestCase;

final class CacheApplicationExceptionTest extends TestCase
{
    public function testDiagnosticRangeIsClosedAndStringificationIsNondisclosing(): void
    {
        self::assertSame([
            'KGN-CACHE-APPLICATION-2041', 'KGN-CACHE-APPLICATION-2042', 'KGN-CACHE-APPLICATION-2043', 'KGN-CACHE-APPLICATION-2044',
            'KGN-CACHE-APPLICATION-2045', 'KGN-CACHE-APPLICATION-2046', 'KGN-CACHE-APPLICATION-2047', 'KGN-CACHE-APPLICATION-2048',
        ], array_column(CacheApplicationDiagnostic::cases(), 'value'));
        $failure = InvalidCacheApplicationException::invalid('private-value');
        self::assertSame('KGN-CACHE-APPLICATION-2041', $failure->diagnosticId());
        self::assertStringNotContainsString('private-value', (string) $failure);
    }
}
