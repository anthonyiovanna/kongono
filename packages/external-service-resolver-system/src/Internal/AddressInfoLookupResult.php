<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceResolverSystem\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use LogicException;

/** Carries either a detached nonempty record list or one closed lookup failure. */
#[Api(ApiStatus::Internal)]
final readonly class AddressInfoLookupResult
{
    /** @var list<AddressInfoRecord> */
    private array $records;

    /** @param list<AddressInfoRecord> $records */
    private function __construct(array $records, private ?AddressInfoLookupFailure $failure)
    {
        if (([] === $records) === (null === $failure)) {
            throw new LogicException('Address-information result shape is invalid.');
        }
        $this->records = [...$records];
    }

    /** @param list<AddressInfoRecord> $records */
    public static function succeeded(array $records): self
    {
        return new self($records, null);
    }

    public static function failed(AddressInfoLookupFailure $failure): self
    {
        return new self([], $failure);
    }

    /** @return list<AddressInfoRecord> */
    public function records(): array
    {
        return [...$this->records];
    }

    public function failure(): ?AddressInfoLookupFailure
    {
        return $this->failure;
    }
}
