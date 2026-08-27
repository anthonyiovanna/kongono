<?php

declare(strict_types=1);

namespace Kongono\IdentityAccess\Tests\Unit;

use Kongono\IdentityAccess\AuthorizationDecision;
use PHPUnit\Framework\TestCase;
use ReflectionEnum;

final class AuthorizationDecisionTest extends TestCase
{
    public function testDecisionVocabularyIsExactAndInert(): void
    {
        self::assertSame(['permit', 'deny', 'indeterminate'], array_column(AuthorizationDecision::cases(), 'value'));
        self::assertSame(AuthorizationDecision::Permit, AuthorizationDecision::from('permit'));
        self::assertNotSame(AuthorizationDecision::Permit, AuthorizationDecision::Deny);

        $reflection = new ReflectionEnum(AuthorizationDecision::class);
        self::assertTrue($reflection->isBacked());
        self::assertSame(['name', 'value'], array_map(static fn(\ReflectionProperty $property): string => $property->getName(), $reflection->getProperties()));
        foreach (['allows', 'authorize', 'evaluate', 'reason', 'policy', 'receipt'] as $method) {
            self::assertFalse($reflection->hasMethod($method));
        }
    }
}
