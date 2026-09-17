<?php

declare(strict_types=1);

namespace Kongono\CacheContract\Tests\Unit\Outcome;

use Kongono\CacheContract\Outcome\CacheLookupOutcome;
use Kongono\CacheContract\Outcome\CacheMutationOutcome;
use PHPUnit\Framework\TestCase;

final class CacheOutcomeTest extends TestCase
{
    public function testLookupAndMutationCasesOrderAndValuesAreExact(): void
    {
        self::assertSame(['fresh_hit', 'miss', 'expired', 'rejected', 'unavailable'], array_column(CacheLookupOutcome::cases(), 'value'));
        self::assertSame(['stored', 'removed', 'not_found', 'conflict', 'rejected', 'unavailable'], array_column(CacheMutationOutcome::cases(), 'value'));
        self::assertNotSame(CacheLookupOutcome::Miss->value, (new \Kongono\CacheContract\Value\CacheValueBytes(''))->bytes());
    }
}
