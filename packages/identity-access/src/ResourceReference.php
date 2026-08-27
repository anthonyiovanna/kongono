<?php

declare(strict_types=1);

namespace Kongono\IdentityAccess;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Pairs one resource category with one exact opaque identifier. */
#[Api(ApiStatus::Experimental)]
final readonly class ResourceReference
{
    public function __construct(
        private ResourceType $type,
        private ResourceId $id,
    ) {}

    public function type(): ResourceType
    {
        return $this->type;
    }

    public function id(): ResourceId
    {
        return $this->id;
    }

    public function equals(self $other): bool
    {
        return $this->type->equals($other->type) && $this->id->equals($other->id);
    }
}
