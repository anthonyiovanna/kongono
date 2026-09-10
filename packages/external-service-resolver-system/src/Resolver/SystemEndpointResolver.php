<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceResolverSystem\Resolver;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExternalServiceContract\Resolution\EndpointResolver;
use Kongono\ExternalServiceResolverSystem\Internal\NativeAddressInfoLookup;
use Kongono\ExternalServiceResolverSystem\Internal\SystemResolverEngine;
use Kongono\ExternalServiceResolverSystem\Internal\SystemResolverEnvironment;
use Kongono\ExternalServiceResolverSystem\Profile\SystemResolverProfile;
use Kongono\NetworkEgressContract\Origin\CanonicalOrigin;
use Kongono\NetworkEgressRuntime\Resolver\EgressResolutionResult;

/** Resolves one origin through one fresh bounded operating-system lookup. */
#[Api(ApiStatus::Experimental)]
final class SystemEndpointResolver implements EndpointResolver
{
    public function __construct(private readonly SystemResolverProfile $profile)
    {
        SystemResolverEnvironment::assertSupported();
    }

    public function resolve(CanonicalOrigin $origin): EgressResolutionResult
    {
        return (new SystemResolverEngine(new NativeAddressInfoLookup()))->resolve($origin, $this->profile);
    }
}
