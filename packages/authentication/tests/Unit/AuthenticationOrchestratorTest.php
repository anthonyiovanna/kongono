<?php

declare(strict_types=1);

namespace Kongono\Authentication\Tests\Unit;

use Kongono\Authentication\AuthenticationEvidence;
use Kongono\Authentication\AuthenticationFacts;
use Kongono\Authentication\AuthenticationInstant;
use Kongono\Authentication\AuthenticationMechanism;
use Kongono\Authentication\AuthenticationOrchestrator;
use Kongono\Authentication\AuthenticationRequest;
use Kongono\Authentication\AuthenticationRequirement;
use Kongono\Authentication\AuthenticationStrength;
use Kongono\Authentication\AuthenticationVerification;
use Kongono\Authentication\AuthenticationVerificationOperation;
use Kongono\Authentication\AuthenticationVerifier;
use Kongono\Authentication\Exception\InvalidAuthenticationValueException;
use Kongono\IdentityAccess\AuthenticationOutcomeKind;
use Kongono\IdentityAccess\PrincipalId;
use Kongono\IdentityAccess\PrincipalKind;
use Kongono\IdentityAccess\PrincipalReference;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use stdClass;

final class AuthenticationOrchestratorTest extends TestCase
{
    public function testRegistrationBoundsMembersAndUniqueMechanismsAreExact(): void
    {
        foreach ([[], array_fill(0, 33, $this->verifier('synthetic.same', AuthenticationVerification::invalid()))] as $invalid) {
            try {
                new AuthenticationOrchestrator($invalid);
                self::fail('Invalid verifier count accepted.');
            } catch (InvalidAuthenticationValueException $failure) {
                self::assertSame('KGN-AUTHENTICATION-1002', $failure->diagnosticId());
            }
        }

        $this->expectException(InvalidAuthenticationValueException::class);
        new AuthenticationOrchestrator([
            $this->verifier('synthetic.same', AuthenticationVerification::invalid()),
            $this->verifier('synthetic.same', AuthenticationVerification::invalid()),
        ]);
    }

    public function testRegistrationRejectsInvalidMembersAtRuntime(): void
    {
        $reflection = new ReflectionClass(AuthenticationOrchestrator::class);

        $this->expectException(InvalidAuthenticationValueException::class);
        $reflection->newInstance([new stdClass()]);
    }

    public function testZeroMultipleAndUnmatchedEvidenceDoNotBeginAnyVerifier(): void
    {
        $verifier = $this->verifier('synthetic.match', AuthenticationVerification::invalid());
        $orchestrator = new AuthenticationOrchestrator([$verifier]);

        self::assertSame(AuthenticationOutcomeKind::Absent, $orchestrator->authenticate($this->request([]))->outcome()->kind());
        self::assertSame(AuthenticationOutcomeKind::Ambiguous, $orchestrator->authenticate($this->request([
            new OrchestratorEvidence('synthetic.match'),
            new OrchestratorEvidence('synthetic.other'),
        ]))->outcome()->kind());
        self::assertSame(AuthenticationOutcomeKind::Unsupported, $orchestrator->authenticate($this->request([
            new OrchestratorEvidence('synthetic.other'),
        ]))->outcome()->kind());
        self::assertSame(0, $verifier->beginCalls);
    }

    public function testExactMechanismSelectionHasNoFallbackAndOrdersLifecycle(): void
    {
        $first = $this->verifier('synthetic.first', AuthenticationVerification::invalid());
        $log = new OrchestratorLog();
        $second = $this->verifier('synthetic.second', AuthenticationVerification::replayed(), $log);
        $orchestrator = new AuthenticationOrchestrator([$first, $second]);

        $verification = $orchestrator->authenticate($this->request([new OrchestratorEvidence('synthetic.second')]));

        self::assertSame(AuthenticationOutcomeKind::Replayed, $verification->outcome()->kind());
        self::assertSame(0, $first->beginCalls);
        self::assertSame(1, $second->beginCalls);
        self::assertSame(['begin', 'verify', 'retire'], $log->events);
    }

    public function testAuthenticatedFactsMustMatchMechanismAndExplicitTimeAndStrength(): void
    {
        self::assertSame(AuthenticationOutcomeKind::Invalid, $this->authenticateFacts(
            $this->facts('synthetic.other', 2, 100, 200),
            150,
            1,
            100,
        ));
        self::assertSame(AuthenticationOutcomeKind::Invalid, $this->authenticateFacts(
            $this->facts('synthetic.match', 2, 151, 200),
            150,
            1,
            100,
        ));
        self::assertSame(AuthenticationOutcomeKind::Expired, $this->authenticateFacts(
            $this->facts('synthetic.match', 2, 100, 149),
            150,
            1,
            100,
        ));
        self::assertSame(AuthenticationOutcomeKind::Expired, $this->authenticateFacts(
            $this->facts('synthetic.match', 2, 100, 300),
            150,
            1,
            49,
        ));
        self::assertSame(AuthenticationOutcomeKind::Invalid, $this->authenticateFacts(
            $this->facts('synthetic.match', 1, 100, 300),
            150,
            2,
            100,
        ));
    }

    public function testValidityAgeAndStrengthEqualityBoundariesRemainAuthenticated(): void
    {
        self::assertSame(AuthenticationOutcomeKind::Authenticated, $this->authenticateFacts(
            $this->facts('synthetic.match', 2, 100, 150),
            150,
            2,
            50,
        ));
    }

    private function authenticateFacts(
        AuthenticationFacts $facts,
        int $now,
        int $requiredStrength,
        int $maximumAge,
    ): AuthenticationOutcomeKind {
        $verification = AuthenticationVerification::authenticated($facts);
        $orchestrator = new AuthenticationOrchestrator([$this->verifier('synthetic.match', $verification)]);

        return $orchestrator->authenticate($this->request(
            [new OrchestratorEvidence('synthetic.match')],
            $now,
            $requiredStrength,
            $maximumAge,
        ))->outcome()->kind();
    }

    private function facts(string $mechanism, int $strength, int $authenticatedAt, int $validUntil): AuthenticationFacts
    {
        return new AuthenticationFacts(
            new PrincipalReference(PrincipalKind::Human, new PrincipalId('principal-orchestrator')),
            new AuthenticationMechanism($mechanism),
            new AuthenticationStrength($strength),
            new AuthenticationInstant($authenticatedAt),
            new AuthenticationInstant($validUntil),
        );
    }

    /** @param list<AuthenticationEvidence> $evidence */
    private function request(array $evidence, int $now = 150, int $strength = 1, int $age = 100): AuthenticationRequest
    {
        return new AuthenticationRequest(
            $evidence,
            new AuthenticationInstant($now),
            new AuthenticationRequirement(new AuthenticationStrength($strength), $age),
        );
    }

    private function verifier(
        string $mechanism,
        AuthenticationVerification $verification,
        ?OrchestratorLog $log = null,
    ): OrchestratorVerifier {
        $log ??= new OrchestratorLog();

        return new OrchestratorVerifier(
            new AuthenticationMechanism($mechanism),
            $verification,
            $log,
        );
    }
}

final readonly class OrchestratorEvidence implements AuthenticationEvidence
{
    private AuthenticationMechanism $mechanism;

    public function __construct(string $mechanism)
    {
        $this->mechanism = new AuthenticationMechanism($mechanism);
    }

    public function mechanism(): AuthenticationMechanism
    {
        return $this->mechanism;
    }
}

final class OrchestratorLog
{
    /** @var list<string> */
    public array $events = [];
}

final class OrchestratorVerifier implements AuthenticationVerifier
{
    public int $beginCalls = 0;

    public function __construct(
        private readonly AuthenticationMechanism $mechanism,
        private readonly AuthenticationVerification $verification,
        private readonly OrchestratorLog $log,
    ) {}

    public function mechanism(): AuthenticationMechanism
    {
        return $this->mechanism;
    }

    public function begin(AuthenticationEvidence $evidence, AuthenticationInstant $now): AuthenticationVerificationOperation
    {
        ++$this->beginCalls;
        $this->log->events[] = 'begin';

        return new OrchestratorOperation($this->verification, $this->log);
    }
}

final class OrchestratorOperation implements AuthenticationVerificationOperation
{
    public function __construct(
        private readonly AuthenticationVerification $verification,
        private readonly OrchestratorLog $log,
    ) {}

    public function verify(): AuthenticationVerification
    {
        $this->log->events[] = 'verify';

        return $this->verification;
    }

    public function retire(): void
    {
        $this->log->events[] = 'retire';
    }
}
