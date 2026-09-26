<?php

declare(strict_types=1);

namespace Kongono\StorageContract\Tests\Unit\Digest;

use Kongono\StorageContract\Digest\StorageContentDigest;
use Kongono\StorageContract\Digest\StorageDigestAlgorithm;
use Kongono\StorageContract\Exception\InvalidStorageContractException;
use PHPUnit\Framework\TestCase;

final class StorageContentDigestTest extends TestCase
{
    public function testAlgorithmsRequireExactLowercaseHexLengths(): void
    {
        self::assertSame(['sha256', 'sha384', 'sha512'], array_column(StorageDigestAlgorithm::cases(), 'value'));
        foreach ([[StorageDigestAlgorithm::Sha256, 64], [StorageDigestAlgorithm::Sha384, 96], [StorageDigestAlgorithm::Sha512, 128]] as [$algorithm, $length]) {
            $digest = new StorageContentDigest($algorithm, str_repeat('a', $length));
            self::assertSame($algorithm, $digest->algorithm());
            self::assertSame(str_repeat('a', $length), $digest->digest());
            self::assertTrue($digest->equals(new StorageContentDigest($algorithm, str_repeat('a', $length))));
        }
    }

    public function testMalformedDigestsFailClosed(): void
    {
        foreach ([str_repeat('a', 63), str_repeat('A', 64), str_repeat('g', 64)] as $value) {
            try {
                new StorageContentDigest(StorageDigestAlgorithm::Sha256, $value);
                self::fail('Expected invalid digest.');
            } catch (InvalidStorageContractException $failure) {
                self::assertSame('KGN-STORAGE-CONTRACT-2103', $failure->diagnosticId());
            }
        }
    }
}
