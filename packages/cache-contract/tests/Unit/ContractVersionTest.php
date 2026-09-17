<?php

declare(strict_types=1);

namespace Kongono\CacheContract\Tests\Unit;

use Kongono\CacheContract\ContractVersion;
use PHPUnit\Framework\TestCase;

final class ContractVersionTest extends TestCase
{
    public function testVersionOneIsTheOnlyExactContractVersion(): void
    {
        self::assertSame([ContractVersion::Version1], ContractVersion::cases());
        self::assertSame(1, ContractVersion::Version1->value);
    }
}
