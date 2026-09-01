<?php

declare(strict_types=1);

namespace Kongono\AgentDeclarationBuild\Tests\Support;

use Kongono\AgentDeclarationBuild\Declaration\AgentDeclaration;
use Kongono\AgentDeclarationBuild\Declaration\AgentDeclarationIdentity;
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
use Kongono\AgentPrincipalContract\Provenance\AgentProvenance;
use Kongono\AgentPrincipalContract\Provenance\ProvenanceSource;
use Kongono\AgentPrincipalContract\Visibility\AgentVisibility;
use Kongono\IdentityAccess\PrincipalId;
use Kongono\IdentityAccess\PrincipalKind;
use Kongono\IdentityAccess\PrincipalReference;

final class AgentDeclarationFactory
{
    public static function create(string $identity = 'assistant.primary', string $principalId = 'principal-one'): AgentDeclaration
    {
        return new AgentDeclaration(
            new AgentDeclarationIdentity($identity),
            new AgentPrincipalReference(new PrincipalReference(PrincipalKind::Agent, new PrincipalId($principalId))),
            [
                new CapabilityRequest(
                    new CapabilityIdentity('calendar.read'),
                    new CapabilityPurpose('Read a bounded calendar view'),
                    CapabilityRequirement::Required,
                ),
                new CapabilityRequest(
                    new CapabilityIdentity('mail.read'),
                    new CapabilityPurpose('Read bounded mail metadata'),
                    CapabilityRequirement::Optional,
                ),
            ],
            [
                new AgentConstraint(ConstraintKind::StepLimit, 4),
                new AgentConstraint(ConstraintKind::TokenLimit, 2048),
            ],
            [
                new EvidenceReference(EvidenceKind::Authentication, new EvidenceIdentity('auth.primary'), str_repeat('1', 64)),
                new EvidenceReference(EvidenceKind::Policy, new EvidenceIdentity('policy.primary'), str_repeat('2', 64)),
            ],
            new AgentProvenance(ProvenanceSource::Application, 'application.primary', str_repeat('3', 64)),
            AgentVisibility::Protected,
        );
    }

    private function __construct() {}
}
