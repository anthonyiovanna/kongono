<?php

declare(strict_types=1);

namespace Kongono\StorageContract\Tests\Security;

use Kongono\StorageContract\Content\StorageMediaType;
use Kongono\StorageContract\Digest\StorageContentDigest;
use Kongono\StorageContract\Digest\StorageDigestAlgorithm;
use Kongono\StorageContract\Exception\InvalidStorageContractException;
use Kongono\StorageContract\Identity\LogicalObjectIdentity;
use Kongono\StorageContract\Identity\StorageNamespace;
use Kongono\StorageContract\Metadata\StorageMetadataEntry;
use Kongono\StorageContract\Operation\StorageOperationIdentity;
use PHPUnit\Framework\TestCase;

final class StorageContractDisclosureTest extends TestCase
{
    public function testRejectedRawValuesNeverReachPackageOwnedPresentation(): void
    {
        $privateInputs = [
            'PRIVATE-NAMESPACE' => static fn() => new StorageNamespace('PRIVATE-NAMESPACE'),
            'PRIVATE/OBJECT' => static fn() => new LogicalObjectIdentity('PRIVATE/OBJECT'),
            'PRIVATE OPERATION' => static fn() => new StorageOperationIdentity('PRIVATE OPERATION'),
            'TEXT/PRIVATE' => static fn() => new StorageMediaType('TEXT/PRIVATE'),
            'PRIVATE-NAME' => static fn() => new StorageMetadataEntry('PRIVATE-NAME', 'value'),
            'PRIVATE-DIGEST' => static fn() => new StorageContentDigest(StorageDigestAlgorithm::Sha256, 'PRIVATE-DIGEST'),
        ];
        foreach ($privateInputs as $private => $construct) {
            try {
                $construct();
                self::fail('Expected rejected raw value.');
            } catch (InvalidStorageContractException $failure) {
                self::assertStringNotContainsString($private, $failure->getMessage());
                self::assertStringNotContainsString($private, (string) $failure);
                foreach ($failure->diagnosticContext()->fields() as $field) {
                    self::assertStringNotContainsString($private, (string) $field->value());
                }
            }
        }
    }

    public function testSequentialAndReentrantConstructionSharesNoState(): void
    {
        $first = new StorageNamespace('first');
        $inner = (static fn(): StorageNamespace => new StorageNamespace('inner'))();
        $second = new StorageNamespace('second');
        self::assertSame('first', $first->value());
        self::assertSame('inner', $inner->value());
        self::assertSame('second', $second->value());
        self::assertFalse(class_exists('Kongono\\StorageContract\\Provider'));
        self::assertFalse(interface_exists('Kongono\\StorageContract\\Adapter'));
        self::assertFalse(class_exists('Kongono\\StorageContract\\Runtime'));
    }
}
