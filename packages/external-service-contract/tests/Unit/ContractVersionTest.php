<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceContract\Tests\Unit;

use Kongono\ExternalServiceContract\ContractVersion;
use PHPUnit\Framework\TestCase;

final class ContractVersionTest extends TestCase
{
    public function testCurrentVersionIsExact(): void
    {
        self::assertSame(1, ContractVersion::CURRENT);
    }
}
