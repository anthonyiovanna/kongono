<?php

declare(strict_types=1);

namespace Kongono\StorageContract\Tests\Unit\Outcome;

use Kongono\StorageContract\Outcome\StorageListOutcome;
use Kongono\StorageContract\Outcome\StorageMutationOutcome;
use Kongono\StorageContract\Outcome\StorageReadOutcome;
use PHPUnit\Framework\TestCase;

final class StorageOutcomeTest extends TestCase
{
    public function testOutcomeVocabulariesPreserveDistinctTruth(): void
    {
        self::assertSame(['completed', 'absent', 'rejected', 'unsupported', 'unavailable'], array_column(StorageReadOutcome::cases(), 'value'));
        self::assertSame(['completed', 'absent', 'already_exists', 'conflict', 'rejected', 'unsupported', 'unavailable', 'uncertain'], array_column(StorageMutationOutcome::cases(), 'value'));
        self::assertSame(['completed', 'rejected', 'unsupported', 'unavailable'], array_column(StorageListOutcome::cases(), 'value'));
    }
}
