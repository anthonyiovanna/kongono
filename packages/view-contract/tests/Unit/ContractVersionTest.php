<?php

declare(strict_types=1);

namespace Kongono\ViewContract\Tests\Unit;

use Kongono\ViewContract\ContractVersion;
use PHPUnit\Framework\TestCase;

final class ContractVersionTest extends TestCase
{
    public function testVersionInventoryIsExact(): void
    {
        self::assertSame([1], array_column(ContractVersion::cases(), 'value'));
    }
}
