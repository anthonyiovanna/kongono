<?php

declare(strict_types=1);

namespace Kongono\AssetBuild\Tests\Unit\Source;

use Kongono\AssetBuild\Source\AssetSource;
use Kongono\AssetBuild\Source\AssetSourcePath;
use Kongono\AssetContract\Identity\LogicalAssetIdentity;
use PHPUnit\Framework\TestCase;

final class AssetSourceTest extends TestCase
{
    public function testExactEmptyAndBinaryBytesArePreserved(): void
    {
        $empty = new AssetSource(new LogicalAssetIdentity('empty'), new AssetSourcePath('assets/empty.bin'), '');
        $binary = new AssetSource(new LogicalAssetIdentity('binary'), new AssetSourcePath('assets/binary.bin'), "\0\xff\r\n");
        self::assertSame('', $empty->bytes());
        self::assertSame(0, $empty->byteCount());
        self::assertSame("\0\xff\r\n", $binary->bytes());
        self::assertSame(hash('sha256', "\0\xff\r\n"), $binary->contentIdentity()->value());
    }
}
