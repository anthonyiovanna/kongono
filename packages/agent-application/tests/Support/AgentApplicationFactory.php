<?php

declare(strict_types=1);

namespace Kongono\AgentApplication\Tests\Support;

use Kongono\AgentApplication\Generated\AgentWiringGenerator;
use Kongono\AgentApplication\Generated\GeneratedAgentWiring;
use Kongono\AgentApplication\Profile\AgentApplicationProfile;
use Kongono\AgentApplication\Profile\AgentApplicationProfileFactory;
use Kongono\AgentApplication\Runtime\AgentApplicationRunInput;
use Kongono\AgentDeclarationBuild\Artifact\AgentDeclarationArtifactBuilder;
use Kongono\AgentDeclarationBuild\Declaration\AgentDeclaration;
use Kongono\AgentRuntime\Tests\Support\AgentRuntimeFactory;
use Kongono\AgentRuntime\Tests\Support\RecordingAuthorityCurrentness;
use Kongono\AgentRuntime\Tests\Support\RecordingCancellation;
use Kongono\AgentRuntime\Tests\Support\RecordingCapabilityPort;
use Kongono\AgentRuntime\Tests\Support\RecordingClock;
use Kongono\AgentRuntime\Tests\Support\RecordingProposalPort;

final class AgentApplicationFactory
{
    public static function declaration(string $capability = 'calendar.read'): AgentDeclaration
    {
        return AgentRuntimeFactory::declaration([AgentRuntimeFactory::capability($capability)]);
    }

    public static function profile(string $capability = 'calendar.read'): AgentApplicationProfile
    {
        $declaration = self::declaration($capability);
        $bytes = (new AgentDeclarationArtifactBuilder())->build($declaration)->canonicalBytes();

        return (new AgentApplicationProfileFactory())->create($declaration, $bytes, AgentRuntimeFactory::plan($declaration));
    }

    public static function wiring(string $capability = 'calendar.read'): GeneratedAgentWiring
    {
        return (new AgentWiringGenerator())->generate(self::profile($capability));
    }

    public static function runInput(AgentDeclaration $declaration, RecordingProposalPort $proposal, RecordingCapabilityPort $capability): AgentApplicationRunInput
    {
        return new AgentApplicationRunInput(
            AgentRuntimeFactory::operation(),
            AgentRuntimeFactory::intent(),
            AgentRuntimeFactory::budget(),
            $proposal,
            [$capability],
            new RecordingAuthorityCurrentness(),
            new RecordingCancellation(),
            new RecordingClock(),
        );
    }

    private function __construct() {}
}
