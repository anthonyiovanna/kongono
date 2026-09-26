<?php

declare(strict_types=1);

namespace Kongono\StorageAdapterContract\Tests\Unit\Diagnostic;

use Kongono\StorageAdapterContract\Diagnostic\StorageAdapterContractDiagnostic;
use PHPUnit\Framework\TestCase;

final class StorageAdapterContractDiagnosticTest extends TestCase
{
    public function testIdentifiersAreUnique(): void
    {
        $v = array_column(StorageAdapterContractDiagnostic::cases(), 'value');
        self::assertSame($v, array_values(array_unique($v)));
    }
}
