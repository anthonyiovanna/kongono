<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressApplication\Workflow;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressApplication\Generated\GeneratedNetworkEgressWiring;
use Kongono\NetworkEgressApplication\Profile\NetworkEgressApplicationProfile;
use Kongono\NetworkEgressApplication\Runtime\LoadedNetworkEgressWiring;

/** Returns exact-current profile, generated, published-path, and one-start evidence. */
#[Api(ApiStatus::Experimental)]
final readonly class CurrentNetworkEgressApplicationBuild
{
    public function __construct(
        private NetworkEgressApplicationProfile $profile,
        private GeneratedNetworkEgressWiring $generatedWiring,
        private string $publishedPath,
        private LoadedNetworkEgressWiring $loadedWiring,
    ) {}

    public function profile(): NetworkEgressApplicationProfile
    {
        return $this->profile;
    }

    public function generatedWiring(): GeneratedNetworkEgressWiring
    {
        return $this->generatedWiring;
    }

    public function publishedPath(): string
    {
        return $this->publishedPath;
    }

    public function loadedWiring(): LoadedNetworkEgressWiring
    {
        return $this->loadedWiring;
    }
}
