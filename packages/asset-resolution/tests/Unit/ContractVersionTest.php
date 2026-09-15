<?php

declare(strict_types=1);

namespace Kongono\AssetResolution\Tests\Unit;

use Kongono\AssetResolution\ContractVersion;
use PHPUnit\Framework\TestCase;

final class ContractVersionTest extends TestCase
{
    public function testVersionOneIsClosed(): void
    {
        self::assertSame([ContractVersion::Version1], ContractVersion::cases());
        self::assertSame(1, ContractVersion::Version1->value);
    }
}
