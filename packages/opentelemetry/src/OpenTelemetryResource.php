<?php

declare(strict_types=1);

namespace Kongono\OpenTelemetry;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\OpenTelemetry\Exception\InvalidOpenTelemetryConfigurationException;
use Kongono\OpenTelemetry\Internal\OpenTelemetryValidation;

/** Retains only the three explicitly admitted OpenTelemetry resource values. */
#[Api(ApiStatus::Experimental)]
final readonly class OpenTelemetryResource
{
    public function __construct(
        private string $serviceName,
        private ?string $serviceVersion = null,
        private ?string $deploymentEnvironmentName = null,
    ) {
        if (
            !OpenTelemetryValidation::boundedText($serviceName, 128, false)
            || !OpenTelemetryValidation::optionalText($serviceVersion, 64)
            || !OpenTelemetryValidation::optionalText($deploymentEnvironmentName, 64)
        ) {
            throw InvalidOpenTelemetryConfigurationException::invalid();
        }
    }

    public function serviceName(): string
    {
        return $this->serviceName;
    }

    public function serviceVersion(): ?string
    {
        return $this->serviceVersion;
    }

    public function deploymentEnvironmentName(): ?string
    {
        return $this->deploymentEnvironmentName;
    }
}
