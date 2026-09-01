<?php

declare(strict_types=1);

namespace Kongono\AgentApplication\Generated;

use Kongono\AgentApplication\Internal\GeneratedWiringEmitter;
use Kongono\AgentApplication\Internal\GeneratedWiringImplementation;
use Kongono\AgentApplication\Profile\AgentApplicationProfile;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Generates deterministic inert PHP for one exact application profile. */
#[Api(ApiStatus::Experimental)]
final class AgentWiringGenerator
{
    public const int MAX_GENERATED_BYTES = 1_048_576;

    public function generate(AgentApplicationProfile $profile): GeneratedAgentWiring
    {
        $profileIdentity = $profile->identity()->value();
        $namespace = 'KongonoGenerated\\Agent\\V1\\P' . $profileIdentity;
        $metadata = [
            'schema' => 'kongono.agent-application.generated-wiring',
            'version' => 1,
            'profile_identity' => $profileIdentity,
            'declaration_artifact_sha256' => $profile->declarationArtifact()->artifactSha256(),
            'declaration_identity' => $profile->declaration()->identity()->value(),
            'admission_identity' => $profile->authorityPlan()->identity()->value(),
            'permitted_capability_identities' => array_map(static fn($identity): string => $identity->value(), $profile->permittedCapabilityIdentities()),
            'generator_implementation_sha256' => GeneratedWiringImplementation::sha256(),
            'generated_namespace' => $namespace,
            'generated_class' => $namespace . '\\AgentApplicationBoundary',
            'generated_interface' => GeneratedAgentApplicationBoundary::class,
        ];
        $bytes = GeneratedWiringEmitter::emit($metadata);

        return (new AgentWiringValidator())->validateStructure($bytes);
    }
}
