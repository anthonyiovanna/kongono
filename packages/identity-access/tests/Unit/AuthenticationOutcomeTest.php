<?php

declare(strict_types=1);

namespace Kongono\IdentityAccess\Tests\Unit;

use Kongono\IdentityAccess\AuthenticationOutcome;
use Kongono\IdentityAccess\AuthenticationOutcomeKind;
use Kongono\IdentityAccess\PrincipalId;
use Kongono\IdentityAccess\PrincipalKind;
use Kongono\IdentityAccess\PrincipalReference;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class AuthenticationOutcomeTest extends TestCase
{
    public function testEveryOutcomeFactoryHasExactKindAndPrincipalPresence(): void
    {
        $principal = new PrincipalReference(PrincipalKind::Human, new PrincipalId('principal-1'));
        $outcomes = [
            [AuthenticationOutcome::authenticated($principal), AuthenticationOutcomeKind::Authenticated, $principal],
            [AuthenticationOutcome::absent(), AuthenticationOutcomeKind::Absent, null],
            [AuthenticationOutcome::invalid(), AuthenticationOutcomeKind::Invalid, null],
            [AuthenticationOutcome::expired(), AuthenticationOutcomeKind::Expired, null],
            [AuthenticationOutcome::replayed(), AuthenticationOutcomeKind::Replayed, null],
            [AuthenticationOutcome::unsupported(), AuthenticationOutcomeKind::Unsupported, null],
            [AuthenticationOutcome::ambiguous(), AuthenticationOutcomeKind::Ambiguous, null],
            [AuthenticationOutcome::unavailable(), AuthenticationOutcomeKind::Unavailable, null],
        ];

        foreach ($outcomes as [$outcome, $kind, $expectedPrincipal]) {
            self::assertSame($kind, $outcome->kind());
            self::assertSame($expectedPrincipal, $outcome->principal());
        }

        self::assertSame(
            ['authenticated', 'absent', 'invalid', 'expired', 'replayed', 'unsupported', 'ambiguous', 'unavailable'],
            array_column(AuthenticationOutcomeKind::cases(), 'value'),
        );
    }

    public function testOutcomeShapeContainsNoAuthorizationOrEvidenceApi(): void
    {
        $reflection = new ReflectionClass(AuthenticationOutcome::class);

        self::assertTrue($reflection->isFinal());
        self::assertTrue($reflection->isReadOnly());
        self::assertTrue($reflection->getConstructor()?->isPrivate());
        self::assertSame(
            ['absent', 'ambiguous', 'authenticated', 'expired', 'invalid', 'kind', 'principal', 'replayed', 'unavailable', 'unsupported'],
            $this->declaredPublicMethods($reflection),
        );
        foreach (['authorize', 'allows', 'credential', 'evidence', 'provider', 'reason', '__toString', '__serialize', '__debugInfo'] as $method) {
            self::assertFalse($reflection->hasMethod($method));
        }
        self::assertSame(['kind', 'principal'], array_map(static fn(\ReflectionProperty $property): string => $property->getName(), $reflection->getProperties()));
    }

    /**
     * @template T of object
     * @param ReflectionClass<T> $reflection
     * @return list<string>
     */
    private function declaredPublicMethods(ReflectionClass $reflection): array
    {
        $methods = [];
        foreach ($reflection->getMethods() as $method) {
            if ($method->getDeclaringClass()->getName() === $reflection->getName() && $method->isPublic()) {
                $methods[] = $method->getName();
            }
        }
        sort($methods);

        return $methods;
    }
}
