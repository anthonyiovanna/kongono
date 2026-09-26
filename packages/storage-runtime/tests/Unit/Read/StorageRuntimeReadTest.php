<?php

declare(strict_types=1);

namespace Kongono\StorageRuntime\Tests\Unit\Read;

use Kongono\StorageContract\Outcome\StorageReadOutcome;
use Kongono\StorageContract\Result\StorageReadResult;
use Kongono\StorageRuntime\Read\StorageRuntimeRead;
use PHPUnit\Framework\TestCase;

final class StorageRuntimeReadTest extends TestCase
{
    public function testAbsentReadHasNoSource(): void
    {
        $read = new StorageRuntimeRead(new StorageReadResult(StorageReadOutcome::Absent));
        self::assertSame(StorageReadOutcome::Absent, $read->result()->outcome());
        self::assertNull($read->source());
    }
}
