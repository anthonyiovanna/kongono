<?php

declare(strict_types=1);

namespace Kongono\AgentAdmission\Tests\Unit\Authority;

use Kongono\AgentAdmission\Admission\AgentAdmissionBuilder;
use Kongono\AgentAdmission\Authority\AgentAuthorityPlan;
use Kongono\AgentAdmission\Authority\CapabilityAuthorityDecision;
use Kongono\AgentAdmission\Authority\CapabilityAuthorityStatus;
use Kongono\AgentAdmission\Exception\InvalidAgentAdmissionException;
use Kongono\AgentAdmission\Identity\AgentAdmissionIdentity;
use Kongono\AgentAdmission\Tests\Support\AgentAdmissionFactory;
use Kongono\AgentPrincipalContract\Capability\CapabilityIdentity;
use Kongono\AgentPrincipalContract\Capability\CapabilityRequirement;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class AgentAuthorityPlanTest extends TestCase
{
    public function testPlanListsAreDefensiveAndPublicSurfaceHasNoAuthorityVerb(): void
    {
        $plan = (new AgentAdmissionBuilder())->build(AgentAdmissionFactory::request(
            requested: [new CapabilityIdentity('calendar.read')],
            evidence: [AgentAdmissionFactory::evidence('calendar.read')],
        ));
        $decisions = $plan->capabilityDecisions();
        array_pop($decisions);
        self::assertCount(1, $plan->capabilityDecisions());
        $constraints = $plan->constraints();
        array_pop($constraints);
        self::assertCount(1, $plan->constraints());
        $evidence = $plan->evidenceReferences();
        array_pop($evidence);
        self::assertCount(1, $plan->evidenceReferences());

        $forbidden = ['execute', 'invoke', 'call', 'run', 'resolve', 'get', 'authorize', 'grant', 'tool', 'model', 'provider', 'dispatch'];
        foreach ((new ReflectionClass(AgentAuthorityPlan::class))->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            self::assertNotContains(strtolower($method->getName()), $forbidden);
        }
    }

    public function testIdentityIsExactConstantTimeValueAndDecisionShapesFailClosed(): void
    {
        $identity = new AgentAdmissionIdentity(str_repeat('a', 64));
        self::assertTrue($identity->equals(new AgentAdmissionIdentity(str_repeat('a', 64))));
        self::assertFalse($identity->equals(new AgentAdmissionIdentity(str_repeat('b', 64))));

        try {
            new AgentAdmissionIdentity('RAW-REJECTED-IDENTITY');
            self::fail('Expected identity failure.');
        } catch (InvalidAgentAdmissionException $failure) {
            self::assertStringNotContainsString('RAW-REJECTED-IDENTITY', (string) $failure);
        }

        $this->expectException(InvalidAgentAdmissionException::class);
        new CapabilityAuthorityDecision(
            new CapabilityIdentity('calendar.read'),
            CapabilityRequirement::Required,
            CapabilityAuthorityStatus::Permitted,
            null,
        );
    }
}
