<?php

declare(strict_types=1);

namespace Kongono\StorageContract\Tests\Unit\Pagination;

use Kongono\StorageContract\Exception\InvalidStorageContractException;
use Kongono\StorageContract\Pagination\StorageContinuationToken;
use Kongono\StorageContract\Version\StorageObjectVersion;
use PHPUnit\Framework\TestCase;

final class StorageContinuationTokenTest extends TestCase
{
    public function testOpaqueBase64UrlEvidenceIsExactAndComparable(): void
    {
        $token = new StorageContinuationToken('YWJjXzEyMy0');
        self::assertSame('YWJjXzEyMy0', $token->value());
        self::assertTrue($token->equals(new StorageContinuationToken('YWJjXzEyMy0')));
        self::assertSame('djE', (new StorageObjectVersion('djE'))->value());
    }

    public function testMalformedAndOverlongEvidenceFailsClosed(): void
    {
        foreach ([
            static fn() => new StorageContinuationToken(''),
            static fn() => new StorageContinuationToken('abc='),
            static fn() => new StorageContinuationToken(str_repeat('a', 513)),
            static fn() => new StorageObjectVersion('abc='),
            static fn() => new StorageObjectVersion(str_repeat('a', 257)),
        ] as $construct) {
            try {
                $construct();
                self::fail('Expected invalid opaque evidence.');
            } catch (InvalidStorageContractException $failure) {
                self::assertContains($failure->diagnosticId(), ['KGN-STORAGE-CONTRACT-2103', 'KGN-STORAGE-CONTRACT-2108']);
            }
        }
    }
}
