<?php

declare(strict_types=1);

namespace Kongono\Observability;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Observability\Exception\InvalidObservabilityValueException;
use Kongono\Observability\Internal\ObservabilityValidation;

/** Versions the exact application-owned meaning of one neutral signal. */
#[Api(ApiStatus::Experimental)]
final readonly class SignalIdentity
{
    public function __construct(private string $name, private int $schemaVersion)
    {
        if (!ObservabilityValidation::isAttributeKey($name) || $schemaVersion < 1 || $schemaVersion > 65_535) {
            throw InvalidObservabilityValueException::signalIdentity();
        }
    }

    public function name(): string
    {
        return $this->name;
    }

    public function schemaVersion(): int
    {
        return $this->schemaVersion;
    }

    public function equals(self $other): bool
    {
        return $this->name === $other->name && $this->schemaVersion === $other->schemaVersion;
    }
}
