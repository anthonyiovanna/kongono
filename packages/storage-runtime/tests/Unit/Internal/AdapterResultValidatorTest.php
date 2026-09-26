<?php

declare(strict_types=1);

namespace Kongono\StorageRuntime\Tests\Unit\Internal;

use Kongono\StorageContract\Outcome\StorageReadOutcome;
use Kongono\StorageContract\Result\StorageReadResult;
use Kongono\StorageRuntime\Internal\AdapterResultValidator;
use Kongono\StorageRuntime\Tests\Fixture\ScriptedStorageAdapter;
use PHPUnit\Framework\TestCase;

final class AdapterResultValidatorTest extends TestCase
{
    public function testNoncompletedReadRemainsTypedAndDetached(): void
    {
        $result = AdapterResultValidator::read(ScriptedStorageAdapter::set()->profiles()[0], ScriptedStorageAdapter::readRequest(), new StorageReadResult(StorageReadOutcome::Absent));
        self::assertSame(StorageReadOutcome::Absent, $result->outcome());
        self::assertNull($result->evidence());
    }
}
