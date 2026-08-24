<?php

declare(strict_types=1);

namespace Kongono\Web\Runtime;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Web\Application\WebApplication;
use Kongono\Web\Runtime\Exception\InvalidGeneratedWebRuntimeException;

/** Bridges one generated WebApplication to the existing delivery runtime. */
#[Api(ApiStatus::Experimental)]
final class GeneratedWebRuntimeRoot
{
    private readonly WebApplication $application;
    private readonly WebRuntimeAdapter $runtime;

    public function __construct(WebApplication $application, GeneratedWebRuntimeBootstrap $bootstrap)
    {
        $this->application = $application;
        $this->runtime = new WebRuntimeAdapter($application, $bootstrap->permission());
    }

    public function runtime(): WebRuntimeAdapter
    {
        if ($this->isRetired()) {
            throw new InvalidGeneratedWebRuntimeException('application_retired');
        }

        return $this->runtime;
    }

    public function isRetired(): bool
    {
        return $this->application->isRetired() || $this->runtime->isRetired();
    }
}
