<?php

declare(strict_types=1);

namespace Kongono\IdentityAccess;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Pairs one principal kind with one exact opaque identifier. */
#[Api(ApiStatus::Experimental)]
final readonly class PrincipalReference
{
    public function __construct(
        private PrincipalKind $kind,
        private PrincipalId $id,
    ) {}

    public function kind(): PrincipalKind
    {
        return $this->kind;
    }

    public function id(): PrincipalId
    {
        return $this->id;
    }

    public function equals(self $other): bool
    {
        return $this->kind === $other->kind && $this->id->equals($other->id);
    }
}
