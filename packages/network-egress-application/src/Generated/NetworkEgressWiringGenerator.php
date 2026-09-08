<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressApplication\Generated;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressApplication\Internal\CanonicalProfile;
use Kongono\NetworkEgressApplication\Internal\GeneratedWiringEmitter;
use Kongono\NetworkEgressApplication\Internal\GeneratedWiringImplementation;
use Kongono\NetworkEgressApplication\Profile\NetworkEgressApplicationProfile;

/** Generates deterministic inert PHP for one exact application profile. */
#[Api(ApiStatus::Experimental)]
final class NetworkEgressWiringGenerator
{
    public const int MAX_GENERATED_BYTES = 1_048_576;

    public function generate(NetworkEgressApplicationProfile $profile): GeneratedNetworkEgressWiring
    {
        $identity = $profile->identity()->value();
        $source = $profile->source();
        $artifact = $profile->artifact();
        $namespace = 'KongonoGenerated\\NetworkEgress\\V1\\P' . $identity;
        $metadata = [
            'schema' => 'kongono.network-egress-application.generated-wiring',
            'version' => 1,
            'contract_version' => 1,
            'application_profile_identity' => $identity,
            'policy_identity' => $source->identity()->value(),
            'artifact_sha256' => $artifact->artifactSha256(),
            'source_sha256' => $artifact->sourceSha256(),
            'required_capabilities_sha256' => CanonicalProfile::capabilitySha256($source),
            'generator_implementation_sha256' => GeneratedWiringImplementation::sha256(),
            'generated_namespace' => $namespace,
            'generated_class' => $namespace . '\\NetworkEgressApplicationBoundary',
            'generated_interface' => GeneratedNetworkEgressApplicationBoundary::class,
        ];

        return (new NetworkEgressWiringValidator())->validateStructure(GeneratedWiringEmitter::emit($metadata));
    }
}
