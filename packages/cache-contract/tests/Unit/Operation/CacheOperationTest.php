<?php

declare(strict_types=1);

namespace Kongono\CacheContract\Tests\Unit\Operation;

use Kongono\CacheContract\Operation\CacheOperation;
use PHPUnit\Framework\TestCase;

final class CacheOperationTest extends TestCase
{
    public function testCasesOrderAndValuesAreExact(): void
    {
        self::assertSame(['lookup', 'store', 'replace', 'remove'], array_column(CacheOperation::cases(), 'value'));
    }
}
