<?php

declare(strict_types=1);

namespace Kongono\StorageRuntime\Tests\Unit\Diagnostic;

use Kongono\StorageRuntime\Diagnostic\StorageRuntimeDiagnostic;
use PHPUnit\Framework\TestCase;

final class StorageRuntimeDiagnosticTest extends TestCase
{
    public function testIdentifiersAreClosedAndConsecutive(): void
    {
        self::assertSame(['KGN-STORAGE-RUNTIME-2131','KGN-STORAGE-RUNTIME-2132','KGN-STORAGE-RUNTIME-2133','KGN-STORAGE-RUNTIME-2134','KGN-STORAGE-RUNTIME-2135','KGN-STORAGE-RUNTIME-2136','KGN-STORAGE-RUNTIME-2137'], array_column(StorageRuntimeDiagnostic::cases(), 'value'));
    }
}
