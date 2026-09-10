<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceResolverSystem\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExternalServiceResolverSystem\Profile\SystemResolverProfile;
use Kongono\NetworkEgressContract\Failure\EgressFailure;
use Kongono\NetworkEgressContract\Failure\EgressFailureKind;
use Kongono\NetworkEgressContract\Origin\CanonicalOrigin;
use Kongono\NetworkEgressRuntime\Resolver\EgressResolutionResult;

/** Coordinates one lookup and one complete deterministic normalization pass. */
#[Api(ApiStatus::Internal)]
final readonly class SystemResolverEngine
{
    public function __construct(private AddressInfoLookup $lookup) {}

    public function resolve(CanonicalOrigin $origin, SystemResolverProfile $profile): EgressResolutionResult
    {
        $flags = AI_NUMERICSERV;
        if (false !== @inet_pton($origin->host())) {
            $flags |= AI_NUMERICHOST;
        }
        $result = $this->lookup->lookup($origin->host(), (string) $origin->port(), [
            'ai_socktype' => SOCK_STREAM,
            'ai_protocol' => SOL_TCP,
            'ai_flags' => $flags,
        ], $profile->maximumCandidates());
        if (null !== $result->failure()) {
            return self::failure($result->failure());
        }

        return AddressInfoNormalizer::normalize($result->records(), $origin->port(), $profile->addressFamilyPreference());
    }

    public static function failure(AddressInfoLookupFailure $failure): EgressResolutionResult
    {
        return new EgressResolutionResult(null, new EgressFailure(EgressFailureKind::Resolution, $failure->value));
    }
}
