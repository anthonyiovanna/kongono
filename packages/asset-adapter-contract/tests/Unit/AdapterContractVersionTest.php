<?php

declare(strict_types=1);

namespace Kongono\AssetAdapterContract\Tests\Unit;

use Kongono\AssetAdapterContract\AdapterContractVersion;
use PHPUnit\Framework\TestCase;

final class AdapterContractVersionTest extends TestCase
{
    public function testVersionIsClosed(): void
    {
        self::assertSame([1], array_column(AdapterContractVersion::cases(), 'value'));
    }
}
