<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressRuntime\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressPolicyBuild\Artifact\EgressPolicyArtifact;
use Kongono\NetworkEgressPolicyBuild\Artifact\EgressPolicyArtifactValidator;
use Kongono\NetworkEgressPolicyBuild\Policy\AddressScope;
use Kongono\NetworkEgressPolicyBuild\Policy\CookieMode;
use Kongono\NetworkEgressPolicyBuild\Policy\CredentialMode;
use Kongono\NetworkEgressPolicyBuild\Policy\ProxyMode;
use Kongono\NetworkEgressRuntime\Address\AddressCategory;
use Kongono\NetworkEgressRuntime\Address\ResolutionEvidence;
use Kongono\NetworkEgressRuntime\Exception\InvalidEgressRuntimeException;
use Kongono\NetworkEgressRuntime\Execution\EgressRuntimeRequest;

/** Applies deterministic admission relations before caller-owned effects. */
#[Api(ApiStatus::Internal)]
final class RuntimeGuard
{
    public static function validatePolicy(EgressRuntimeRequest $request): EgressPolicyArtifact
    {
        $artifact = (new EgressPolicyArtifactValidator())->validateCurrent($request->policySource(), $request->policyArtifactBytes());
        $operation = $request->operation();
        if (!$operation->policyReference()->equals($artifact->policyReference())) {
            throw InvalidEgressRuntimeException::invalid('policy_reference');
        }
        if (!$operation->budget()->equals($request->policySource()->budget())) {
            throw InvalidEgressRuntimeException::invalid('budget_reference');
        }
        $matches = 0;
        foreach ($request->policySource()->allowedOrigins() as $origin) {
            if ($origin->equals($operation->origin())) {
                ++$matches;
            }
        }
        if (1 !== $matches) {
            throw InvalidEgressRuntimeException::invalid('origin_denied');
        }
        HeaderMeter::enforce($operation->request(), $operation->budget());
        if (CookieMode::Deny === $request->policySource()->cookieMode() && $operation->request()->hasHeader('Cookie')) {
            throw InvalidEgressRuntimeException::invalid('header_posture');
        }
        if (CredentialMode::None === $request->policySource()->credentialMode() && ($operation->request()->hasHeader('Authorization') || $operation->request()->hasHeader('Proxy-Authorization'))) {
            throw InvalidEgressRuntimeException::invalid('header_posture');
        }
        if (ProxyMode::ExplicitOnly === $request->policySource()->proxyMode()) {
            throw InvalidEgressRuntimeException::invalid('proxy_unsupported');
        }

        return $artifact;
    }

    public static function validateResolution(EgressRuntimeRequest $request, ResolutionEvidence $evidence): void
    {
        $addresses = $evidence->addresses();
        if (count($addresses) > $request->operation()->budget()->maximumResolutionCandidates()) {
            throw InvalidEgressRuntimeException::invalid('resolution_candidates');
        }
        foreach ($addresses as $address) {
            $allowed = AddressScope::PublicOnly === $request->policySource()->addressScope()
                ? AddressCategory::Public === $address->category()
                : in_array($address->category(), [AddressCategory::Public, AddressCategory::Private, AddressCategory::Loopback], true);
            if (!$allowed) {
                throw InvalidEgressRuntimeException::invalid('address_scope');
            }
        }
        $hostBinary = @inet_pton($request->operation()->origin()->host());
        if (false !== $hostBinary && $request->operation()->origin()->host() !== $evidence->selectedAddress()->value()) {
            throw InvalidEgressRuntimeException::invalid('literal_address');
        }
    }

    private function __construct() {}
}
