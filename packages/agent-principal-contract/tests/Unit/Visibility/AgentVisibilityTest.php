<?php

declare(strict_types=1);

namespace Kongono\AgentPrincipalContract\Tests\Unit\Visibility;

use Kongono\AgentPrincipalContract\Visibility\AgentVisibility;
use PHPUnit\Framework\TestCase;

final class AgentVisibilityTest extends TestCase
{
    public function testVocabularyIsExact(): void
    {
        self::assertSame(['private', 'protected', 'application'], array_map(static fn(AgentVisibility $case): string => $case->value, AgentVisibility::cases()));
    }
}
