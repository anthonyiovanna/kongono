<?php

declare(strict_types=1);

namespace Kongono\ExtensionContract\Tests\Unit;

use Kongono\ExtensionContract\ContractVersion;
use PHPUnit\Framework\TestCase;

final class ContractVersionTest extends TestCase
{
    public function testVersionOneIsTheExactClosedContract(): void
    {
        self::assertSame([['Version1', 1]], array_map(static fn(ContractVersion $case): array => [$case->name, $case->value], ContractVersion::cases()));
    }
}
