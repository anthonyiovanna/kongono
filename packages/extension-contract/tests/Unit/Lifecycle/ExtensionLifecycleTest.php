<?php

declare(strict_types=1);

namespace Kongono\ExtensionContract\Tests\Unit\Lifecycle;

use Kongono\ExtensionContract\Lifecycle\ExtensionLifecycle;
use PHPUnit\Framework\TestCase;

final class ExtensionLifecycleTest extends TestCase
{
    public function testLifecycleIntentIsTheExactClosedScopeVocabulary(): void
    {
        self::assertSame([['Application', 'application'], ['Operation', 'operation']], array_map(static fn(ExtensionLifecycle $case): array => [$case->name, $case->value], ExtensionLifecycle::cases()));
    }
}
