<?php

declare(strict_types=1);

namespace Kongono\StorageAdapterContract\Tests\Unit;

use Kongono\StorageAdapterContract\AdapterContractVersion;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AdapterContractVersion::class)]
final class AdapterContractVersionTest extends TestCase
{
    public function testVersionIsClosed(): void
    {
        self::assertSame([1], array_column(AdapterContractVersion::cases(), 'value'));
    }
}
