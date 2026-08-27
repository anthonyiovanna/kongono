<?php

declare(strict_types=1);

namespace Kongono\SecurityHttp\Tests\Unit;

use Kongono\Authentication\AuthenticationFacts;
use Kongono\Authentication\AuthenticationInstant;
use Kongono\Authentication\AuthenticationOrchestrator;
use Kongono\Authentication\AuthenticationRequirement;
use Kongono\Authentication\AuthenticationStrength;
use Kongono\Authentication\AuthenticationVerification;
use Kongono\Composition\Runtime\LoadedGeneratedWiring;
use Kongono\Http\Message\Message\ServerRequest;
use Kongono\IdentityAccess\PrincipalId;
use Kongono\IdentityAccess\PrincipalKind;
use Kongono\IdentityAccess\PrincipalReference;
use Kongono\IdentityAccess\SecurityContextState;
use Kongono\SecurityHttp\BrowserSessionProfile;
use Kongono\SecurityHttp\BrowserSessionSecurityGateway;
use Kongono\SecurityHttp\Exception\BrowserSessionAuthenticationException;
use Kongono\SecurityHttp\Exception\BrowserSessionRequestException;
use Kongono\SecurityHttp\Tests\Fixture\TestApplicationAssembly;
use Kongono\SecurityHttp\Tests\Fixture\TestBrowserSessionVerifier;
use Kongono\SecurityHttp\Tests\Fixture\TestOperationAssembly;
use Kongono\SecurityHttp\TrustedOrigin;
use Kongono\SecurityRuntime\GeneratedSecurityApplication;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use RuntimeException;

final class BrowserSessionSecurityGatewayTest extends TestCase
{
    private const string SESSION = 'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA';
    private const string CSRF = 'BBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBB';

    protected function setUp(): void
    {
        TestApplicationAssembly::reset();
    }

    public function testNoCookieBeginsExactlyOneAnonymousOperationWithoutAuthentication(): void
    {
        [$gateway, $verifier] = $this->gateway(AuthenticationVerification::invalid());
        $request = (new ServerRequest('GET', 'https://example.test/path'))
            ->withAttribute('principal', 'must-not-grant-authority');

        $operation = $gateway->begin($request, new AuthenticationInstant(100));

        self::assertSame(SecurityContextState::Anonymous, $operation->securityContext()->state());
        self::assertSame(0, $verifier->beginCalls);
        self::assertSame(1, TestApplicationAssembly::$beginCalls);
        $operation->close();
        $operation->close();
        self::assertSame(1, TestOperationAssembly::$closeCalls);
    }

    public function testSafeMethodsAuthenticateExactlyOnceWithoutCsrf(): void
    {
        foreach (['GET', 'HEAD', 'OPTIONS'] as $method) {
            TestApplicationAssembly::reset();
            [$gateway, $verifier] = $this->gateway($this->authenticated());
            $operation = $gateway->begin($this->request($method), new AuthenticationInstant(100));

            self::assertSame(SecurityContextState::Authenticated, $operation->securityContext()->state());
            self::assertSame(1, $verifier->beginCalls);
            self::assertSame(1, $verifier->verifyCalls);
            self::assertSame(1, $verifier->retireCalls);
            self::assertNotNull($verifier->lastEvidence);
            self::assertFalse($verifier->lastEvidence->requiresCsrfProof());
            self::assertNull($verifier->lastEvidence->csrfProof());
            $operation->close();
        }
    }

    public function testStateChangingRequestProvidesExactProofOnlyToSelectedVerifier(): void
    {
        [$gateway, $verifier] = $this->gateway($this->authenticated());
        $operation = $gateway->begin($this->request('POST', [
            'Origin' => 'https://example.test',
            BrowserSessionProfile::CSRF_HEADER_NAME => self::CSRF,
        ]), new AuthenticationInstant(100));

        self::assertNotNull($verifier->lastEvidence);
        self::assertTrue($verifier->lastEvidence->requiresCsrfProof());
        self::assertSame(self::CSRF, $verifier->lastEvidence->csrfProof()?->sensitiveValue());
        self::assertSame(self::SESSION, $verifier->lastEvidence->sessionIdentifier()->sensitiveValue());
        $operation->close();
    }

    public function testMalformedCookieTransportAndCsrfFailBeforeAuthenticationOrStartup(): void
    {
        $cases = [
            new ServerRequest('GET', 'https://example.test', [], ['Cookie' => ['a=b', 'c=d']]),
            new ServerRequest('GET', 'https://example.test', [], ['Cookie' => '__Host-kongono_session=' . self::SESSION . '; __Host-kongono_session=' . self::SESSION]),
            new ServerRequest('GET', 'https://example.test', [], ['Cookie' => str_repeat('a', 4097)]),
            new ServerRequest('GET', 'http://example.test', [], ['Cookie' => '__Host-kongono_session=' . self::SESSION]),
            new ServerRequest('GET', 'https://wrong.test', [], ['Cookie' => '__Host-kongono_session=' . self::SESSION]),
            $this->request('POST'),
            $this->request('POST', ['Origin' => 'https://wrong.test', BrowserSessionProfile::CSRF_HEADER_NAME => self::CSRF]),
            $this->request('POST', ['Origin' => 'https://example.test']),
            $this->request('POST', ['Origin' => 'https://example.test', BrowserSessionProfile::CSRF_HEADER_NAME => ['x', 'y']]),
            $this->request('POST', ['Origin' => 'https://example.test', BrowserSessionProfile::CSRF_HEADER_NAME => str_repeat('C', 42)]),
        ];

        foreach ($cases as $request) {
            TestApplicationAssembly::reset();
            [$gateway, $verifier] = $this->gateway($this->authenticated());
            try {
                $gateway->begin($request, new AuthenticationInstant(100));
                self::fail('invalid browser request accepted');
            } catch (BrowserSessionRequestException $exception) {
                self::assertStringNotContainsString(self::SESSION, (string) $exception);
                self::assertStringNotContainsString(self::CSRF, (string) $exception);
            }
            self::assertSame(0, $verifier->beginCalls);
            self::assertSame(0, TestApplicationAssembly::$beginCalls);
        }
    }

    public function testEveryNonSuccessOutcomeFailsWithoutAnonymousDowngrade(): void
    {
        foreach ([
            AuthenticationVerification::absent(), AuthenticationVerification::invalid(),
            AuthenticationVerification::expired(), AuthenticationVerification::replayed(),
            AuthenticationVerification::unsupported(), AuthenticationVerification::ambiguous(),
            AuthenticationVerification::unavailable(),
        ] as $verification) {
            TestApplicationAssembly::reset();
            [$gateway, $verifier] = $this->gateway($verification);
            try {
                $gateway->begin($this->request('GET'), new AuthenticationInstant(100));
                self::fail('non-success authentication accepted');
            } catch (BrowserSessionAuthenticationException) {
                self::assertSame(1, $verifier->retireCalls);
            }
            self::assertSame(0, TestApplicationAssembly::$beginCalls);
        }
    }

    public function testSequentialAndReentrantCallsRetainNoRequestOrContext(): void
    {
        [$gateway, $verifier] = $this->gateway($this->authenticated());
        $verifier->onVerify = static function () use ($gateway): void {
            $nested = $gateway->begin(new ServerRequest('GET', 'https://example.test'), new AuthenticationInstant(100));
            self::assertSame(SecurityContextState::Anonymous, $nested->securityContext()->state());
            $nested->close();
        };

        $first = $gateway->begin($this->request('GET'), new AuthenticationInstant(100));
        self::assertSame(SecurityContextState::Authenticated, $first->securityContext()->state());
        $first->close();
        $second = $gateway->begin(new ServerRequest('GET', 'https://example.test'), new AuthenticationInstant(100));
        self::assertSame(SecurityContextState::Anonymous, $second->securityContext()->state());
        $second->close();
        self::assertSame(3, TestApplicationAssembly::$beginCalls);
    }

    public function testOperationCleanupFailurePreservesAcceptedRuntimeSemantics(): void
    {
        [$gateway] = $this->gateway($this->authenticated());
        $operation = $gateway->begin($this->request('GET'), new AuthenticationInstant(100));
        $failure = new RuntimeException('adversarial-cleanup-secret');
        TestOperationAssembly::$cleanupFailure = $failure;

        try {
            $operation->close();
            self::fail('cleanup failure not propagated');
        } catch (RuntimeException $actual) {
            self::assertSame($failure, $actual);
        }
        self::assertTrue($operation->isClosed());
    }

    public function testAuthenticationFailuresPreserveOrchestratorLifecycleSemantics(): void
    {
        [$gateway, $verifier] = $this->gateway($this->authenticated());
        $verificationFailure = new RuntimeException('adversarial-verification-secret');
        $verifier->verifyFailure = $verificationFailure;

        try {
            $gateway->begin($this->request('GET'), new AuthenticationInstant(100));
            self::fail('verification failure not propagated');
        } catch (RuntimeException $actual) {
            self::assertSame($verificationFailure, $actual);
        }
        self::assertSame(1, $verifier->retireCalls);
        self::assertSame(0, TestApplicationAssembly::$beginCalls);

        [$gateway, $verifier] = $this->gateway($this->authenticated());
        $retireFailure = new RuntimeException('adversarial-retire-secret');
        $verifier->retireFailure = $retireFailure;
        $this->expectException(\Kongono\Authentication\Exception\AuthenticationCleanupException::class);
        $gateway->begin($this->request('GET'), new AuthenticationInstant(100));
    }

    /** @param array<string, string|list<string>> $headers */
    private function request(string $method, array $headers = []): ServerRequest
    {
        $headers['Cookie'] = '__Host-kongono_session=' . self::SESSION;

        return new ServerRequest($method, 'https://example.test/path', [], $headers);
    }

    /** @return array{BrowserSessionSecurityGateway, TestBrowserSessionVerifier} */
    private function gateway(AuthenticationVerification $verification): array
    {
        $verifier = new TestBrowserSessionVerifier($verification);
        $application = GeneratedSecurityApplication::start(new LoadedGeneratedWiring(
            str_repeat('a', 64),
            TestApplicationAssembly::class,
            TestOperationAssembly::class,
            new ReflectionMethod(TestApplicationAssembly::class, 'start'),
        ));

        return [new BrowserSessionSecurityGateway(
            new TrustedOrigin('https://example.test'),
            new AuthenticationOrchestrator([$verifier]),
            new AuthenticationRequirement(new AuthenticationStrength(1), 300),
            $application,
        ), $verifier];
    }

    private function authenticated(): AuthenticationVerification
    {
        return AuthenticationVerification::authenticated(new AuthenticationFacts(
            new PrincipalReference(PrincipalKind::Human, new PrincipalId('principal-1')),
            new \Kongono\Authentication\AuthenticationMechanism(BrowserSessionProfile::NAME),
            new AuthenticationStrength(1),
            new AuthenticationInstant(90),
            new AuthenticationInstant(200),
        ));
    }
}
