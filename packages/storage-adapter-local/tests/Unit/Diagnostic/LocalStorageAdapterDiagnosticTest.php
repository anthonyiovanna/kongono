<?php

declare(strict_types=1);

namespace Kongono\StorageAdapterLocal\Tests\Unit\Diagnostic;

use Kongono\StorageAdapterLocal\Diagnostic\LocalStorageAdapterDiagnostic;
use PHPUnit\Framework\TestCase;

final class LocalStorageAdapterDiagnosticTest extends TestCase
{
    public function testIdentifiersAreUnique(): void
    {
        $v = array_column(LocalStorageAdapterDiagnostic::cases(), 'value');
        self::assertSame($v, array_values(array_unique($v)));
    }
}
