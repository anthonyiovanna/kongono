<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationContract\Tests\Unit\Lifecycle;

use Kongono\ModelInvocationContract\Lifecycle\InvocationState;
use PHPUnit\Framework\TestCase;

final class InvocationStateTest extends TestCase
{
    public function testVocabularyIsExactAndContainsNoStateMachine(): void
    {
        self::assertSame(['prepared', 'active', 'streaming', 'completed', 'failed', 'cancelled', 'retired'], array_column(InvocationState::cases(), 'value'));
        self::assertSame(['cases', 'from', 'tryFrom'], array_map(static fn(\ReflectionMethod $method): string => $method->name, (new \ReflectionEnum(InvocationState::class))->getMethods()));
    }
}
