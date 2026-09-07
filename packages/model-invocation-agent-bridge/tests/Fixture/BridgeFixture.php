<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationAgentBridge\Tests\Fixture;

use Kongono\AgentPrincipalContract\Capability\CapabilityIdentity;
use Kongono\AgentPrincipalContract\Capability\CapabilityPurpose;
use Kongono\AgentPrincipalContract\Capability\CapabilityRequest;
use Kongono\AgentPrincipalContract\Capability\CapabilityRequirement;
use Kongono\AgentPrincipalContract\Constraint\AgentConstraint;
use Kongono\AgentPrincipalContract\Constraint\ConstraintKind;
use Kongono\AgentPrincipalContract\Evidence\EvidenceIdentity;
use Kongono\AgentPrincipalContract\Evidence\EvidenceKind;
use Kongono\AgentPrincipalContract\Evidence\EvidenceReference;
use Kongono\AgentPrincipalContract\Identity\AgentPrincipalReference;
use Kongono\AgentPrincipalContract\Identity\OperationReference;
use Kongono\AgentPrincipalContract\Intent\AgentIntent;
use Kongono\AgentPrincipalContract\Intent\IntentIdentity;
use Kongono\AgentPrincipalContract\Intent\IntentPurpose;
use Kongono\AgentRuntime\Proposal\AgentProposalRequest;
use Kongono\IdentityAccess\PrincipalId;
use Kongono\IdentityAccess\PrincipalKind;
use Kongono\IdentityAccess\PrincipalReference;
use Kongono\ModelInvocationAgentBridge\Mapping\ActionProposalMapping;
use Kongono\ModelInvocationAgentBridge\Mapping\ProposalUsageSelector;
use Kongono\ModelInvocationContract\Result\ActionName;
use Kongono\ModelInvocationContract\Usage\UsageMetricName;
use Kongono\ModelInvocationContract\Usage\UsageUnitName;

final class BridgeFixture
{
    public static function capability(string $identity = 'calendar.read'): CapabilityRequest
    {
        return new CapabilityRequest(
            new CapabilityIdentity($identity),
            new CapabilityPurpose('Use one explicitly permitted capability'),
            CapabilityRequirement::Required,
        );
    }

    /** @param list<CapabilityIdentity>|null $permitted */
    public static function request(?array $permitted = null): AgentProposalRequest
    {
        return new AgentProposalRequest(
            new AgentPrincipalReference(new PrincipalReference(PrincipalKind::Agent, new PrincipalId('agent.primary'))),
            new OperationReference('operation.primary'),
            new AgentIntent(new IntentIdentity('intent.primary'), new IntentPurpose('Perform one bounded operation')),
            $permitted ?? [new CapabilityIdentity('calendar.read')],
            [new AgentConstraint(ConstraintKind::StepLimit, 1)],
            [new EvidenceReference(EvidenceKind::Policy, new EvidenceIdentity('policy.primary'), str_repeat('a', 64))],
        );
    }

    public static function mapping(
        string $action = 'calendar.read',
        string $capability = 'calendar.read',
        string $metric = 'tokens',
        string $unit = 'count',
    ): ActionProposalMapping {
        return new ActionProposalMapping(
            new ActionName($action),
            self::capability($capability),
            new ProposalUsageSelector(new UsageMetricName($metric), new UsageUnitName($unit)),
        );
    }

    private function __construct() {}
}
