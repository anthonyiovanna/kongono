<?php

declare(strict_types=1);

namespace Kongono\ViewBuild\Tests\Unit\Source;

use Kongono\ViewBuild\Exception\InvalidViewSourceException;
use Kongono\ViewBuild\Source\ViewSourceIdentity;
use PHPUnit\Framework\TestCase;

final class ViewSourceIdentityTest extends TestCase
{
    public function testIdentityIsExactAndStructurallyComparable(): void
    {
        $value = str_repeat('a', 64);
        self::assertSame($value, (new ViewSourceIdentity($value))->value());
        self::assertTrue((new ViewSourceIdentity($value))->equals(new ViewSourceIdentity($value)));
    }

    public function testIdentityRejectsNonLowercaseDigest(): void
    {
        $this->expectException(InvalidViewSourceException::class);
        new ViewSourceIdentity(str_repeat('A', 64));
    }
}
