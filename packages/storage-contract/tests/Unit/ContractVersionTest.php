<?php

declare(strict_types=1);

namespace Kongono\StorageContract\Tests\Unit;

use Kongono\StorageContract\ContractVersion;
use PHPUnit\Framework\TestCase;

final class ContractVersionTest extends TestCase
{
    public function testVersionOneIsTheOnlyContractVersion(): void
    {
        self::assertSame([1], array_column(ContractVersion::cases(), 'value'));
        self::assertSame(ContractVersion::Version1, ContractVersion::from(1));
    }
}
