<?php

declare(strict_types=1);

namespace Kongono\StorageAdapterLocal\Tests\Unit;

use Kongono\StorageAdapterLocal\LocalAdapterVersion;
use PHPUnit\Framework\TestCase;

final class LocalAdapterVersionTest extends TestCase
{
    public function testVersionIsClosed(): void
    {
        self::assertSame([1], array_column(LocalAdapterVersion::cases(), 'value'));
    }
}
