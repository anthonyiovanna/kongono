<?php

declare(strict_types=1);

namespace Kongono\CacheAdapterMemory\Tests\Unit\Configuration;

use Kongono\CacheAdapterMemory\Configuration\MemoryEvictionPolicy;
use PHPUnit\Framework\TestCase;

final class MemoryEvictionPolicyTest extends TestCase
{
    public function testOldestInsertionFirstIsTheOnlyPolicy(): void
    {
        self::assertSame([MemoryEvictionPolicy::OldestInsertionFirst], MemoryEvictionPolicy::cases());
        self::assertSame('oldest_insertion_first', MemoryEvictionPolicy::OldestInsertionFirst->value);
    }
}
