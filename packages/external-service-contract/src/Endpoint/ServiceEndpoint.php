<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceContract\Endpoint;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressContract\Origin\CanonicalOrigin;

/** Combines a Phase 15 canonical origin and a secret-free base path. */
#[Api(ApiStatus::Experimental)]
final readonly class ServiceEndpoint
{
    public function __construct(private CanonicalOrigin $origin, private EndpointBasePath $basePath) {}

    public function origin(): CanonicalOrigin
    {
        return $this->origin;
    }

    public function basePath(): EndpointBasePath
    {
        return $this->basePath;
    }

    public function equals(self $other): bool
    {
        return $this->origin->equals($other->origin) && $this->basePath->equals($other->basePath);
    }
}
