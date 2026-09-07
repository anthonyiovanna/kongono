<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationContract\Tests\Unit\Visibility;

use Kongono\ModelInvocationContract\Visibility\ModelVisibility;
use PHPUnit\Framework\TestCase;

final class ModelVisibilityTest extends TestCase
{
    public function testVocabularyIsExactAndGrantsNoAccess(): void
    {
        self::assertSame(['private', 'protected', 'application'], array_column(ModelVisibility::cases(), 'value'));
        self::assertSame(['cases', 'from', 'tryFrom'], array_map(static fn(\ReflectionMethod $method): string => $method->name, (new \ReflectionEnum(ModelVisibility::class))->getMethods()));
    }
}
