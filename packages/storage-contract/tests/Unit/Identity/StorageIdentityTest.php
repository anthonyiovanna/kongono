<?php

declare(strict_types=1);

namespace Kongono\StorageContract\Tests\Unit\Identity;

use Kongono\StorageContract\Exception\InvalidStorageContractException;
use Kongono\StorageContract\Identity\LogicalObjectIdentity;
use Kongono\StorageContract\Identity\StorageNamespace;
use Kongono\StorageContract\Identity\StorageObjectIdentity;
use Kongono\StorageContract\Operation\StorageOperationIdentity;
use PHPUnit\Framework\TestCase;

final class StorageIdentityTest extends TestCase
{
    public function testIdentityDomainsRemainSeparateAndComparable(): void
    {
        $namespace = new StorageNamespace('tenant.assets');
        $logical = new LogicalObjectIdentity('images.logo-v1');
        $operation = new StorageOperationIdentity('operation_1');
        $identity = new StorageObjectIdentity($namespace, $logical);
        self::assertSame($namespace, $identity->namespace());
        self::assertSame($logical, $identity->logicalIdentity());
        self::assertSame('operation_1', $operation->value());
        self::assertTrue($identity->equals(new StorageObjectIdentity(new StorageNamespace('tenant.assets'), new LogicalObjectIdentity('images.logo-v1'))));
        self::assertFalse($identity->equals(new StorageObjectIdentity(new StorageNamespace('other'), $logical)));
    }

    public function testIdentityGrammarAndBoundsFailClosed(): void
    {
        foreach ([
            static fn() => new StorageNamespace('Upper'),
            static fn() => new StorageNamespace('double..separator'),
            static fn() => new LogicalObjectIdentity('/absolute'),
            static fn() => new LogicalObjectIdentity(str_repeat('a', 256)),
            static fn() => new StorageOperationIdentity('operation/1'),
            static fn() => new StorageOperationIdentity(str_repeat('a', 65)),
        ] as $construct) {
            try {
                $construct();
                self::fail('Expected invalid identity.');
            } catch (InvalidStorageContractException $failure) {
                self::assertContains($failure->diagnosticId(), ['KGN-STORAGE-CONTRACT-2101', 'KGN-STORAGE-CONTRACT-2108']);
            }
        }
    }
}
