<?php

declare(strict_types=1);

namespace Kongono\StorageContract\Tests\Unit\Condition;

use Kongono\StorageContract\Condition\StorageMutationCondition;
use Kongono\StorageContract\Condition\StorageMutationConditionKind;
use Kongono\StorageContract\Version\StorageObjectVersion;
use PHPUnit\Framework\TestCase;

final class StorageMutationConditionTest extends TestCase
{
    public function testConditionFactoriesPreserveExactEvidence(): void
    {
        $absent = StorageMutationCondition::ifAbsent();
        self::assertSame(StorageMutationConditionKind::IfAbsent, $absent->kind());
        self::assertNull($absent->version());
        $version = new StorageObjectVersion('djE');
        $matched = StorageMutationCondition::ifVersion($version);
        self::assertSame(StorageMutationConditionKind::IfVersion, $matched->kind());
        self::assertSame($version, $matched->version());
        self::assertTrue($matched->equals(StorageMutationCondition::ifVersion(new StorageObjectVersion('djE'))));
        self::assertSame(['if_absent', 'if_version'], array_column(StorageMutationConditionKind::cases(), 'value'));
    }
}
