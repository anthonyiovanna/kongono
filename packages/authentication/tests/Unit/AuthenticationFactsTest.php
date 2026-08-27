<?php

declare(strict_types=1);

namespace Kongono\Authentication\Tests\Unit;

use Kongono\Authentication\AuthenticationFacts;
use Kongono\Authentication\AuthenticationInstant;
use Kongono\Authentication\AuthenticationMechanism;
use Kongono\Authentication\AuthenticationStrength;
use Kongono\Authentication\Exception\InvalidAuthenticationValueException;
use Kongono\IdentityAccess\PrincipalId;
use Kongono\IdentityAccess\PrincipalKind;
use Kongono\IdentityAccess\PrincipalReference;
use PHPUnit\Framework\TestCase;

final class AuthenticationFactsTest extends TestCase
{
    public function testFactsPreserveExactObjectsAndInclusiveValidityOrdering(): void
    {
        $principal = new PrincipalReference(PrincipalKind::Human, new PrincipalId('principal-alpha'));
        $mechanism = new AuthenticationMechanism('synthetic.v1');
        $strength = new AuthenticationStrength(3);
        $authenticatedAt = new AuthenticationInstant(100);
        $validUntil = new AuthenticationInstant(100);
        $facts = new AuthenticationFacts($principal, $mechanism, $strength, $authenticatedAt, $validUntil);

        self::assertSame($principal, $facts->principal());
        self::assertSame($mechanism, $facts->mechanism());
        self::assertSame($strength, $facts->strength());
        self::assertSame($authenticatedAt, $facts->authenticatedAt());
        self::assertSame($validUntil, $facts->validUntil());
    }

    public function testFactsRejectValidityBeforeAuthentication(): void
    {
        $this->expectException(InvalidAuthenticationValueException::class);
        new AuthenticationFacts(
            new PrincipalReference(PrincipalKind::Service, new PrincipalId('principal-beta')),
            new AuthenticationMechanism('synthetic.v1'),
            new AuthenticationStrength(2),
            new AuthenticationInstant(101),
            new AuthenticationInstant(100),
        );
    }
}
