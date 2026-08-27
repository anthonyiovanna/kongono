<?php

declare(strict_types=1);

namespace Kongono\Authentication\Tests\Unit;

use Kongono\Authentication\AuthenticationFacts;
use Kongono\Authentication\AuthenticationInstant;
use Kongono\Authentication\AuthenticationMechanism;
use Kongono\Authentication\AuthenticationStrength;
use Kongono\Authentication\AuthenticationVerification;
use Kongono\IdentityAccess\AuthenticationOutcomeKind;
use Kongono\IdentityAccess\PrincipalId;
use Kongono\IdentityAccess\PrincipalKind;
use Kongono\IdentityAccess\PrincipalReference;
use PHPUnit\Framework\TestCase;

final class AuthenticationVerificationTest extends TestCase
{
    public function testAuthenticatedFactoryPreservesFactsAndPrincipalIdentity(): void
    {
        $facts = $this->facts();
        $verification = AuthenticationVerification::authenticated($facts);

        self::assertSame(AuthenticationOutcomeKind::Authenticated, $verification->outcome()->kind());
        self::assertSame($facts, $verification->facts());
        self::assertSame($facts->principal(), $verification->outcome()->principal());
    }

    public function testEveryNonAuthenticatedFactoryHasNoFactsOrPrincipal(): void
    {
        $verifications = [
            [AuthenticationVerification::absent(), AuthenticationOutcomeKind::Absent],
            [AuthenticationVerification::invalid(), AuthenticationOutcomeKind::Invalid],
            [AuthenticationVerification::expired(), AuthenticationOutcomeKind::Expired],
            [AuthenticationVerification::replayed(), AuthenticationOutcomeKind::Replayed],
            [AuthenticationVerification::unsupported(), AuthenticationOutcomeKind::Unsupported],
            [AuthenticationVerification::ambiguous(), AuthenticationOutcomeKind::Ambiguous],
            [AuthenticationVerification::unavailable(), AuthenticationOutcomeKind::Unavailable],
        ];

        foreach ($verifications as [$verification, $kind]) {
            self::assertSame($kind, $verification->outcome()->kind());
            self::assertNull($verification->outcome()->principal());
            self::assertNull($verification->facts());
        }
    }

    private function facts(): AuthenticationFacts
    {
        return new AuthenticationFacts(
            new PrincipalReference(PrincipalKind::Human, new PrincipalId('principal-gamma')),
            new AuthenticationMechanism('synthetic.v1'),
            new AuthenticationStrength(2),
            new AuthenticationInstant(100),
            new AuthenticationInstant(200),
        );
    }
}
