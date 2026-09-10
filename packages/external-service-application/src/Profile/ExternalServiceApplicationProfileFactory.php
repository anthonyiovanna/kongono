<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceApplication\Profile;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExternalServiceApplication\Exception\InvalidExternalServiceApplicationProfileException;
use Kongono\ExternalServiceApplication\Internal\CanonicalProfile;
use Kongono\ExternalServiceBindingBuild\Artifact\ExternalServiceBindingArtifactValidator;
use Kongono\ExternalServiceBindingBuild\Source\ExternalServiceBindingSource;
use Kongono\ExternalServiceContract\Binding\ServiceBinding;
use Kongono\ExternalServiceContract\Binding\ServiceBindingIdentity;
use Kongono\NetworkEgressApplication\Profile\NetworkEgressApplicationProfile;
use Kongono\NetworkEgressApplication\Profile\NetworkEgressApplicationProfileFactory;
use Kongono\NetworkEgressPolicyBuild\Policy\CredentialMode;
use Throwable;

/** Binds one exact-current service binding to one exact-current Phase 15 profile. */
#[Api(ApiStatus::Experimental)]
final class ExternalServiceApplicationProfileFactory
{
    public function create(
        ExternalServiceBindingSource $source,
        string $artifactBytes,
        ServiceBindingIdentity $bindingIdentity,
        NetworkEgressApplicationProfile $networkProfile,
    ): ExternalServiceApplicationProfile {
        try {
            $artifact = (new ExternalServiceBindingArtifactValidator())->validateCurrent($source, $artifactBytes);
        } catch (Throwable $failure) {
            throw InvalidExternalServiceApplicationProfileException::invalid('binding_artifact_mismatch', $failure);
        }

        try {
            $currentNetworkProfile = (new NetworkEgressApplicationProfileFactory())->create(
                $networkProfile->source(),
                $networkProfile->artifact()->canonicalBytes(),
            );
        } catch (Throwable $failure) {
            throw InvalidExternalServiceApplicationProfileException::invalid('network_profile_mismatch', $failure);
        }
        if (!$currentNetworkProfile->identity()->equals($networkProfile->identity())) {
            throw InvalidExternalServiceApplicationProfileException::invalid('network_profile_mismatch');
        }

        $selected = array_values(array_filter(
            $artifact->bindings(),
            static fn(ServiceBinding $binding): bool => $binding->identity()->equals($bindingIdentity),
        ));
        if (1 !== count($selected)) {
            throw InvalidExternalServiceApplicationProfileException::invalid('binding_selection');
        }
        $binding = $selected[0];

        $originCount = 0;
        foreach ($networkProfile->source()->allowedOrigins() as $origin) {
            if ($origin->equals($binding->endpoint()->origin())) {
                ++$originCount;
            }
        }
        if (1 !== $originCount) {
            throw InvalidExternalServiceApplicationProfileException::invalid('binding_origin');
        }
        if (CredentialMode::OperationScoped !== $networkProfile->source()->credentialMode()) {
            throw InvalidExternalServiceApplicationProfileException::invalid('credential_mode');
        }
        foreach ($binding->requiredCapabilities()->all() as $required) {
            if (!$networkProfile->source()->requiredCapabilities()->contains($required)) {
                throw InvalidExternalServiceApplicationProfileException::invalid('required_capability');
            }
        }

        $identity = new ExternalServiceApplicationProfileIdentity(hash(
            'sha256',
            CanonicalProfile::bytes($artifact, $binding, $networkProfile),
        ));

        return new ExternalServiceApplicationProfile($source, $artifact, $binding, $networkProfile, $identity);
    }
}
