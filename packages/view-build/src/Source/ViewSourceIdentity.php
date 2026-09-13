<?php

declare(strict_types=1);

namespace Kongono\ViewBuild\Source;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ViewBuild\Exception\InvalidViewSourceException;

/** Carries the lowercase SHA-256 identity of exact accepted source bytes. */
#[Api(ApiStatus::Experimental)]
final readonly class ViewSourceIdentity
{
    public function __construct(private string $value)
    {
        if (1 !== preg_match('/\A[a-f0-9]{64}\z/D', $value)) {
            throw InvalidViewSourceException::invalid('source_identity_grammar');
        }
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
