<?php

declare(strict_types=1);

namespace Kongono\SecurityRuntime\Tests\Unit;

use Kongono\Authentication\AuthenticationFacts;
use Kongono\Authentication\AuthenticationInstant;
use Kongono\Authentication\AuthenticationMechanism;
use Kongono\Authentication\AuthenticationStrength;
use Kongono\Authentication\AuthenticationVerification;
use Kongono\IdentityAccess\AuthenticationOutcome;
use Kongono\IdentityAccess\PrincipalId;
use Kongono\IdentityAccess\PrincipalKind;
use Kongono\IdentityAccess\PrincipalReference;
use Kongono\IdentityAccess\SecurityContextState;
use Kongono\SecurityRuntime\Exception\InvalidSecurityContextVerificationException;
use Kongono\SecurityRuntime\SecurityContextFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class SecurityContextFactoryTest extends TestCase
{
    public function testAnonymousAndAuthenticatedConstructionAreFreshExplicitAndIdentityExact(): void
    {
        $factory = new SecurityContextFactory();
        $firstAnonymous = $factory->anonymous();
        $secondAnonymous = $factory->anonymous();
        self::assertSame(SecurityContextState::Anonymous, $firstAnonymous->state());
        self::assertNull($firstAnonymous->principal());
        self::assertNotSame($firstAnonymous, $secondAnonymous);

        $principal = self::principal('authenticated-principal');
        $verification = AuthenticationVerification::authenticated(self::facts($principal));
        $first = $factory->authenticated($verification);
        $second = $factory->authenticated($verification);
        self::assertSame(SecurityContextState::Authenticated, $first->state());
        self::assertSame($principal, $first->principal());
        self::assertNotSame($first, $second);
        self::assertSame(['anonymous', 'authenticated', 'principal', 'state'], self::publicMethodNames($first));
    }

    /** @return iterable<string, array{AuthenticationVerification}> */
    public static function unsuccessfulVerifications(): iterable
    {
        yield 'absent' => [AuthenticationVerification::absent()];
        yield 'invalid' => [AuthenticationVerification::invalid()];
        yield 'expired' => [AuthenticationVerification::expired()];
        yield 'replayed' => [AuthenticationVerification::replayed()];
        yield 'unsupported' => [AuthenticationVerification::unsupported()];
        yield 'ambiguous' => [AuthenticationVerification::ambiguous()];
        yield 'unavailable' => [AuthenticationVerification::unavailable()];
    }

    #[DataProvider('unsuccessfulVerifications')]
    public function testEveryNonSuccessOutcomeFailsWithoutAnonymousDowngrade(AuthenticationVerification $verification): void
    {
        $this->expectException(InvalidSecurityContextVerificationException::class);
        (new SecurityContextFactory())->authenticated($verification);
    }

    public function testMissingAndInconsistentAuthenticatedFactsFailClosed(): void
    {
        $first = self::principal('first-principal');
        $second = self::principal('second-principal');
        foreach ([
            self::fabricated(AuthenticationOutcome::authenticated($first), null),
            self::fabricated(AuthenticationOutcome::authenticated($first), self::facts($second)),
        ] as $verification) {
            try {
                (new SecurityContextFactory())->authenticated($verification);
                self::fail('Inconsistent authenticated evidence must fail.');
            } catch (InvalidSecurityContextVerificationException $exception) {
                self::assertSame('KGN-SECURITY-RUNTIME-1001', $exception->diagnosticId());
            }
        }
    }

    public function testAdversarialPrincipalAndFailureMaterialIsNotDisclosed(): void
    {
        $sentinel = 'SECRET-PRINCIPAL-' . bin2hex(random_bytes(8));
        $principal = self::principal($sentinel);
        $verification = self::fabricated(AuthenticationOutcome::authenticated($principal), null);
        try {
            (new SecurityContextFactory())->authenticated($verification);
            self::fail('Missing facts must fail.');
        } catch (InvalidSecurityContextVerificationException $exception) {
            self::assertStringNotContainsString($sentinel, (string) $exception);
            self::assertStringNotContainsString($sentinel, json_encode($exception->diagnosticContext(), JSON_THROW_ON_ERROR));
            self::assertSame('KGN-SECURITY-RUNTIME-1001: Invalid Security Context verification', (string) $exception);
        }
    }

    private static function principal(string $id): PrincipalReference
    {
        return new PrincipalReference(PrincipalKind::Human, new PrincipalId($id));
    }

    private static function facts(PrincipalReference $principal): AuthenticationFacts
    {
        return new AuthenticationFacts(
            $principal,
            new AuthenticationMechanism('test-mechanism'),
            new AuthenticationStrength(2),
            new AuthenticationInstant(100),
            new AuthenticationInstant(200),
        );
    }

    private static function fabricated(AuthenticationOutcome $outcome, ?AuthenticationFacts $facts): AuthenticationVerification
    {
        $reflection = new ReflectionClass(AuthenticationVerification::class);
        $verification = $reflection->newInstanceWithoutConstructor();
        $reflection->getProperty('outcome')->setValue($verification, $outcome);
        $reflection->getProperty('facts')->setValue($verification, $facts);

        return $verification;
    }

    /** @return list<string> */
    private static function publicMethodNames(object $value): array
    {
        $methods = array_map(static fn(\ReflectionMethod $method): string => $method->getName(), (new ReflectionClass($value))->getMethods(\ReflectionMethod::IS_PUBLIC));
        sort($methods);

        return $methods;
    }
}
