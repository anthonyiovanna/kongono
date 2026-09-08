<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressApplication\Profile;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressApplication\Exception\InvalidNetworkEgressApplicationProfileException;
use Kongono\NetworkEgressApplication\Internal\CanonicalProfile;
use Kongono\NetworkEgressPolicyBuild\Artifact\EgressPolicyArtifactValidator;
use Kongono\NetworkEgressPolicyBuild\Policy\EgressPolicySource;
use Throwable;

/** Delegates exact-current proof to Phase 15C and binds one immutable profile. */
#[Api(ApiStatus::Experimental)]
final class NetworkEgressApplicationProfileFactory
{
    public function create(EgressPolicySource $source, string $artifactBytes): NetworkEgressApplicationProfile
    {
        try {
            $artifact = (new EgressPolicyArtifactValidator())->validateCurrent($source, $artifactBytes);
        } catch (Throwable $failure) {
            if ($failure instanceof \Error) {
                throw $failure;
            }
            throw InvalidNetworkEgressApplicationProfileException::invalid('artifact_mismatch', $failure);
        }
        if (!$artifact->policyIdentity()->equals($source->identity())) {
            throw InvalidNetworkEgressApplicationProfileException::invalid('artifact_identity_mismatch');
        }
        $identity = new NetworkEgressApplicationProfileIdentity(hash('sha256', CanonicalProfile::bytes($source, $artifact)));

        return new NetworkEgressApplicationProfile($source, $artifact, $identity);
    }
}
