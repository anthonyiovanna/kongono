<?php

declare(strict_types=1);

namespace Kongono\AssetContract\Tests\Unit\Identity;

use Kongono\AssetContract\Exception\InvalidAssetContractException;
use Kongono\AssetContract\Identity\ContentIdentity;
use Kongono\AssetContract\Identity\LogicalAssetIdentity;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AssetIdentityTest extends TestCase
{
    public function testLogicalAndContentIdentitiesAreExactAndStructurallyEqual(): void
    {
        $logical = new LogicalAssetIdentity('app.shell-main');
        self::assertSame('app.shell-main', $logical->value());
        self::assertTrue($logical->equals(new LogicalAssetIdentity('app.shell-main')));
        self::assertFalse($logical->equals(new LogicalAssetIdentity('app.shell-alt')));

        $content = ContentIdentity::fromBytes('asset-bytes');
        self::assertSame(hash('sha256', 'asset-bytes'), $content->value());
        self::assertTrue($content->equals(new ContentIdentity($content->value())));
    }

    #[DataProvider('invalidLogicalIdentities')]
    public function testInvalidLogicalIdentityGrammarIsRejectedWithoutDisclosure(string $value): void
    {
        try {
            new LogicalAssetIdentity($value);
            self::fail('Expected identity rejection.');
        } catch (InvalidAssetContractException $failure) {
            self::assertSame('KGN-ASSET-CONTRACT-1901', $failure->diagnosticId());
            if ('' !== $value) {
                self::assertStringNotContainsString($value, (string) $failure);
            }
        }
    }

    /** @return iterable<string, array{string}> */
    public static function invalidLogicalIdentities(): iterable
    {
        yield 'empty' => [''];
        yield 'upper' => ['PrivateAsset'];
        yield 'slash' => ['private/asset'];
        yield 'separator' => ['asset..part'];
        yield 'too long' => ['a' . str_repeat('b', 128)];
    }

    public function testInvalidDigestAndOversizedCallerBytesAreRejected(): void
    {
        $this->expectException(InvalidAssetContractException::class);
        new ContentIdentity(str_repeat('A', 64));
    }

    public function testCallerByteHashingBoundIsFailClosed(): void
    {
        $this->expectException(InvalidAssetContractException::class);
        ContentIdentity::fromBytes(str_repeat('x', 67_108_865));
    }
}
