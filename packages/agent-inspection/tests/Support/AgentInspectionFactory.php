<?php

declare(strict_types=1);

namespace Kongono\AgentInspection\Tests\Support;

use Kongono\AgentApplication\Generated\AgentWiringGenerator;
use Kongono\AgentApplication\Generated\GeneratedAgentWiring;
use Kongono\AgentApplication\Profile\AgentApplicationProfile;
use Kongono\AgentApplication\Profile\AgentApplicationProfileFactory;
use Kongono\AgentDeclarationBuild\Artifact\AgentDeclarationArtifactBuilder;
use Kongono\AgentInspection\Inspection\AgentPrincipalInspection;
use Kongono\AgentInspection\Inspection\AgentPrincipalInspectionRequest;
use Kongono\AgentInspection\Inspection\AgentPrincipalInspector;
use Kongono\AgentPrincipalContract\Capability\CapabilityRequirement;
use Kongono\AgentPrincipalContract\Identity\AgentPrincipalReference;
use Kongono\AgentPrincipalContract\Receipt\AgentReceipt;
use Kongono\AgentPrincipalContract\Receipt\ReceiptIdentity;
use Kongono\AgentPrincipalContract\Receipt\ReceiptOutcome;
use Kongono\AgentRuntime\Budget\AgentRuntimeUsage;
use Kongono\AgentRuntime\Runtime\AgentRuntimeOutcome;
use Kongono\AgentRuntime\Runtime\AgentRuntimeResult;
use Kongono\AgentRuntime\Runtime\AgentRuntimeState;
use Kongono\AgentRuntime\Tests\Support\AgentRuntimeFactory;
use Kongono\ApplicationGraphArtifact\Identity\GraphIdentity;
use Kongono\ApplicationGraphContract\ContractVersion;
use Kongono\ApplicationGraphIntelligence\Derivation\ApplicationGraphIntelligenceView;
use Kongono\ApplicationGraphIntelligence\Role\DeveloperIntelligenceRole;

final class AgentInspectionFactory
{
    public static function profile(): AgentApplicationProfile
    {
        $declaration = AgentRuntimeFactory::declaration([
            AgentRuntimeFactory::capability('calendar.read'),
            AgentRuntimeFactory::capability('mail.read', CapabilityRequirement::Optional),
        ]);
        $artifact = (new AgentDeclarationArtifactBuilder())->build($declaration);

        return (new AgentApplicationProfileFactory())->create($declaration, $artifact->canonicalBytes(), AgentRuntimeFactory::plan($declaration));
    }

    public static function wiring(?AgentApplicationProfile $profile = null): GeneratedAgentWiring
    {
        return (new AgentWiringGenerator())->generate($profile ?? self::profile());
    }

    public static function inspection(bool $withRuntime = false): AgentPrincipalInspection
    {
        $profile = self::profile();

        return (new AgentPrincipalInspector())->inspect(new AgentPrincipalInspectionRequest(
            $profile,
            self::wiring($profile),
            $withRuntime ? self::runtimeResult($profile->declaration()->principal()) : null,
        ));
    }

    public static function runtimeResult(AgentPrincipalReference $principal): AgentRuntimeResult
    {
        $proposal = AgentRuntimeFactory::proposal(self::profile()->declaration());
        $receipt = new AgentReceipt(
            new ReceiptIdentity('receipt.primary'),
            $principal,
            AgentRuntimeFactory::operation(),
            $proposal->identity(),
            ReceiptOutcome::Performed,
            str_repeat('c', 64),
            $proposal->provenance(),
            $proposal->visibility(),
        );

        return new AgentRuntimeResult(
            AgentRuntimeOutcome::Performed,
            AgentRuntimeState::Retired,
            new AgentRuntimeUsage(2, 10, 5, 1, 0, 0, 7, 0, 1),
            $proposal,
            AgentRuntimeFactory::capabilityResult(),
            [$receipt],
            true,
        );
    }

    public static function graphView(DeveloperIntelligenceRole $role = DeveloperIntelligenceRole::Hantei): ApplicationGraphIntelligenceView
    {
        return new ApplicationGraphIntelligenceView(
            $role,
            str_repeat('d', 64),
            new GraphIdentity(str_repeat('e', 64)),
            ContractVersion::Version1,
            4,
            4,
            ['composition.input' => 1, 'composition.service' => 1, 'composition.contract' => 1, 'composition.root' => 1],
            ['composition.depends_on' => 1, 'composition.consumes_input' => 1, 'composition.implements_contract' => 1, 'composition.exposes_root' => 1],
        );
    }

    private function __construct() {}
}
