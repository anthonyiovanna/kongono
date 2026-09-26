<?php

declare(strict_types=1);

namespace Kongono\StorageContract\Tests\Unit\Capability;

use Kongono\StorageContract\Capability\StorageCapability;
use Kongono\StorageContract\Capability\StorageCapabilitySet;
use Kongono\StorageContract\Exception\InvalidStorageContractException;
use PHPUnit\Framework\TestCase;

final class StorageCapabilityTest extends TestCase
{
    public function testVocabularyAndSetAreClosedOrderedAndDetached(): void
    {
        self::assertSame(['read', 'create', 'replace', 'delete', 'list', 'metadata', 'content_digest', 'conditional_mutation'], array_column(StorageCapability::cases(), 'value'));
        $input = [StorageCapability::Metadata, StorageCapability::Read, StorageCapability::Read];
        $set = new StorageCapabilitySet($input);
        $input[] = StorageCapability::List;
        self::assertSame([StorageCapability::Read, StorageCapability::Metadata], $set->capabilities());
        self::assertTrue($set->contains(StorageCapability::Read));
        self::assertFalse($set->contains(StorageCapability::List));
        self::assertTrue($set->equals(new StorageCapabilitySet([StorageCapability::Read, StorageCapability::Metadata])));
    }

    public function testInvalidCapabilityCollectionsFailClosed(): void
    {
        foreach ([[1 => StorageCapability::Read], ['read']] as $invalid) {
            try {
                new StorageCapabilitySet($invalid);
                self::fail('Expected invalid capability set.');
            } catch (InvalidStorageContractException $failure) {
                self::assertSame('KGN-STORAGE-CONTRACT-2102', $failure->diagnosticId());
            }
        }
    }
}
