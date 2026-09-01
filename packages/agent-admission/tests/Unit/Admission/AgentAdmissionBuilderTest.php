<?php

declare(strict_types=1);

namespace Kongono\AgentAdmission\Tests\Unit\Admission;

use Kongono\AgentAdmission\Admission\AgentAdmissionBuilder;
use Kongono\AgentAdmission\Admission\AgentAdmissionDecision;
use Kongono\AgentAdmission\Admission\AgentAdmissionRequest;
use Kongono\AgentAdmission\Authority\CapabilityAuthorityStatus;
use Kongono\AgentAdmission\Exception\InvalidAgentAdmissionException;
use Kongono\AgentAdmission\Tests\Support\AgentAdmissionFactory;
use Kongono\AgentDeclarationBuild\Artifact\AgentDeclarationArtifactBuilder;
use Kongono\AgentPrincipalContract\Capability\CapabilityIdentity;
use Kongono\AgentPrincipalContract\Capability\CapabilityRequirement;
use Kongono\Authentication\AuthenticationVerification;
use Kongono\IdentityAccess\AuthorizationDecision;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AgentAdmissionBuilderTest extends TestCase
{
    public function testExactCurrentAuthenticatedPermittedRequiredCapabilityAdmitsDeterministically(): void
    {
        $request = AgentAdmissionFactory::request(
            requested: [new CapabilityIdentity('calendar.read')],
            evidence: [AgentAdmissionFactory::evidence('calendar.read')],
        );
        $first = (new AgentAdmissionBuilder())->build($request);
        $second = (new AgentAdmissionBuilder())->build($request);

        self::assertSame(AgentAdmissionDecision::Admitted, $first->admissionDecision());
        self::assertSame(CapabilityAuthorityStatus::Permitted, $first->capabilityDecisions()[0]->status());
        self::assertSame($first->identity()->value(), $second->identity()->value());
        self::assertMatchesRegularExpression('/\A[a-f0-9]{64}\z/D', $first->identity()->value());
    }

    public function testStaleAndStructurallyInvalidArtifactsAreExceptional(): void
    {
        $declaration = AgentAdmissionFactory::declaration();
        $other = AgentAdmissionFactory::declaration(principalId: 'agent-other');
        $artifact = (new AgentDeclarationArtifactBuilder())->build($other)->canonicalBytes();
        $request = new AgentAdmissionRequest($declaration, $artifact, AgentAdmissionFactory::authenticated(), [], []);

        try {
            (new AgentAdmissionBuilder())->build($request);
            self::fail('Expected stale artifact failure.');
        } catch (InvalidAgentAdmissionException $failure) {
            self::assertSame('KGN-AGENT-ADMISSION-1322', $failure->diagnosticId());
        }

        $request = new AgentAdmissionRequest($declaration, '{}', AgentAdmissionFactory::authenticated(), [], []);
        $this->expectException(InvalidAgentAdmissionException::class);
        (new AgentAdmissionBuilder())->build($request);
    }

    /** @return iterable<string, array{AuthenticationVerification}> */
    public static function deniedAuthentication(): iterable
    {
        yield 'absent' => [AuthenticationVerification::absent()];
        yield 'invalid' => [AuthenticationVerification::invalid()];
        yield 'expired' => [AuthenticationVerification::expired()];
        yield 'replayed' => [AuthenticationVerification::replayed()];
        yield 'unsupported' => [AuthenticationVerification::unsupported()];
        yield 'ambiguous' => [AuthenticationVerification::ambiguous()];
        yield 'unavailable' => [AuthenticationVerification::unavailable()];
    }

    #[DataProvider('deniedAuthentication')]
    public function testEveryNonAuthenticatedOutcomeDenies(AuthenticationVerification $verification): void
    {
        $plan = (new AgentAdmissionBuilder())->build(AgentAdmissionFactory::request(verification: $verification));
        self::assertSame(AgentAdmissionDecision::Denied, $plan->admissionDecision());
        self::assertSame($verification->outcome()->kind(), $plan->authenticationOutcome());
    }

    public function testAuthenticatedPrincipalMismatchDenies(): void
    {
        $plan = (new AgentAdmissionBuilder())->build(AgentAdmissionFactory::request(
            verification: AgentAdmissionFactory::authenticated('agent-other'),
            requested: [new CapabilityIdentity('calendar.read')],
            evidence: [AgentAdmissionFactory::evidence('calendar.read')],
        ));
        self::assertSame(AgentAdmissionDecision::Denied, $plan->admissionDecision());
    }

    public function testRequiredNotRequestedAndRequestedWithoutEvidenceDenyDistinctly(): void
    {
        $notRequested = (new AgentAdmissionBuilder())->build(AgentAdmissionFactory::request());
        self::assertSame(AgentAdmissionDecision::Denied, $notRequested->admissionDecision());
        self::assertSame(CapabilityAuthorityStatus::NotRequested, $notRequested->capabilityDecisions()[0]->status());

        $missing = (new AgentAdmissionBuilder())->build(AgentAdmissionFactory::request(
            requested: [new CapabilityIdentity('calendar.read')],
        ));
        self::assertSame(AgentAdmissionDecision::Denied, $missing->admissionDecision());
        self::assertSame(CapabilityAuthorityStatus::MissingAuthorization, $missing->capabilityDecisions()[0]->status());
    }

    public function testDenyIndeterminateAndUndeclaredRemainDistinctAndDeny(): void
    {
        foreach ([
            AuthorizationDecision::Deny->value => [AuthorizationDecision::Deny, CapabilityAuthorityStatus::Denied],
            AuthorizationDecision::Indeterminate->value => [AuthorizationDecision::Indeterminate, CapabilityAuthorityStatus::Indeterminate],
        ] as [$authorization, $status]) {
            $plan = (new AgentAdmissionBuilder())->build(AgentAdmissionFactory::request(
                requested: [new CapabilityIdentity('calendar.read')],
                evidence: [AgentAdmissionFactory::evidence('calendar.read', $authorization)],
            ));
            self::assertSame(AgentAdmissionDecision::Denied, $plan->admissionDecision());
            self::assertSame($status, $plan->capabilityDecisions()[0]->status());
        }

        $plan = (new AgentAdmissionBuilder())->build(AgentAdmissionFactory::request(
            requested: [new CapabilityIdentity('calendar.read'), new CapabilityIdentity('network.open')],
            evidence: [AgentAdmissionFactory::evidence('calendar.read'), AgentAdmissionFactory::evidence('network.open')],
        ));
        self::assertSame(AgentAdmissionDecision::Denied, $plan->admissionDecision());
        self::assertSame(CapabilityAuthorityStatus::Undeclared, $plan->capabilityDecisions()[1]->status());
        self::assertFalse($plan->capabilityDecisions()[1]->permitted());
    }

    public function testAllOptionalEmptyRequestAdmitsButRequestedOptionalMustPermit(): void
    {
        $declaration = AgentAdmissionFactory::declaration([
            AgentAdmissionFactory::capability('calendar.read', CapabilityRequirement::Optional),
        ]);
        $empty = (new AgentAdmissionBuilder())->build(AgentAdmissionFactory::request(declaration: $declaration));
        self::assertSame(AgentAdmissionDecision::Admitted, $empty->admissionDecision());
        self::assertSame(CapabilityAuthorityStatus::NotRequested, $empty->capabilityDecisions()[0]->status());

        $requested = (new AgentAdmissionBuilder())->build(AgentAdmissionFactory::request(
            declaration: $declaration,
            requested: [new CapabilityIdentity('calendar.read')],
        ));
        self::assertSame(AgentAdmissionDecision::Denied, $requested->admissionDecision());
        self::assertSame(CapabilityAuthorityStatus::MissingAuthorization, $requested->capabilityDecisions()[0]->status());
    }

    public function testEveryAuthorizationReceiptFieldAffectsIdentity(): void
    {
        $first = (new AgentAdmissionBuilder())->build(AgentAdmissionFactory::request(
            requested: [new CapabilityIdentity('calendar.read')],
            evidence: [AgentAdmissionFactory::evidence('calendar.read', policyVersion: 'v1')],
        ));
        $second = (new AgentAdmissionBuilder())->build(AgentAdmissionFactory::request(
            requested: [new CapabilityIdentity('calendar.read')],
            evidence: [AgentAdmissionFactory::evidence('calendar.read', policyVersion: 'v2')],
        ));
        self::assertNotSame($first->identity()->value(), $second->identity()->value());
    }
}
