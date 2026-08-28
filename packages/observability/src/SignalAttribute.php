<?php

declare(strict_types=1);

namespace Kongono\Observability;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Pairs one validated key with one exact immutable scalar value. */
#[Api(ApiStatus::Experimental)]
final readonly class SignalAttribute
{
    public function __construct(private AttributeKey $key, private AttributeValue $value) {}

    public function key(): AttributeKey
    {
        return $this->key;
    }

    public function value(): AttributeValue
    {
        return $this->value;
    }
}
