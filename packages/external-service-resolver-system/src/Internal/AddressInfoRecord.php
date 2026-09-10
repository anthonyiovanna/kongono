<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceResolverSystem\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Detaches one explained native record for complete validation without native retention. */
#[Api(ApiStatus::Internal)]
final readonly class AddressInfoRecord
{
    /** @param array<array-key, mixed> $values */
    public function __construct(private array $values) {}

    /** @return array<array-key, mixed> */
    public function values(): array
    {
        return [...$this->values];
    }
}
