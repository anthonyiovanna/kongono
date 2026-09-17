<?php

declare(strict_types=1);

namespace Kongono\CacheAdapterContract\Tests\Unit;

use Kongono\CacheAdapterContract\AdapterContractVersion;
use PHPUnit\Framework\TestCase;

final class AdapterContractVersionTest extends TestCase
{
    public function testVersionOneIsTheOnlyProtocolVersion(): void
    {
        self::assertSame([AdapterContractVersion::Version1], AdapterContractVersion::cases());
        self::assertSame(1, AdapterContractVersion::Version1->value);
    }
}
