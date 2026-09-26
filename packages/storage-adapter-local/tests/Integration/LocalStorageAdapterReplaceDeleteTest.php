<?php

declare(strict_types=1);

namespace Kongono\StorageAdapterLocal\Tests\Integration;

use Kongono\StorageContract\Outcome\StorageMutationOutcome;
use PHPUnit\Framework\TestCase;

final class LocalStorageAdapterReplaceDeleteTest extends TestCase
{
    public function testConditionalOutcomesRemainDistinct(): void
    {
        self::assertNotSame(StorageMutationOutcome::Conflict, StorageMutationOutcome::Absent);
        self::assertNotSame(StorageMutationOutcome::Uncertain, StorageMutationOutcome::Completed);
    }
}
