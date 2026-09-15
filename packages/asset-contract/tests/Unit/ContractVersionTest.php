<?php

declare(strict_types=1);

namespace Kongono\AssetContract\Tests\Unit;

use Kongono\AssetContract\ContractVersion;
use PHPUnit\Framework\TestCase;

final class ContractVersionTest extends TestCase
{
    public function testVersionInventoryIsExact(): void
    {
        self::assertSame(['Version1'], array_column(ContractVersion::cases(), 'name'));
        self::assertSame([1], array_column(ContractVersion::cases(), 'value'));
    }
}
