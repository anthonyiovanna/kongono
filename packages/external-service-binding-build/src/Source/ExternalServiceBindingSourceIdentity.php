<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceBindingBuild\Source;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExternalServiceBindingBuild\Exception\InvalidExternalServiceBindingSourceException;

/** Names one explicit binding source without locating or authenticating it. */
#[Api(ApiStatus::Experimental)]
final readonly class ExternalServiceBindingSourceIdentity
{
    public function __construct(private string $value)
    {
        $length = strlen($value);
        if ($length < 1 || $length > 128 || 1 !== preg_match('/\A[a-z0-9](?:[a-z0-9._-]*[a-z0-9])?\z/D', $value)) {
            throw InvalidExternalServiceBindingSourceException::invalid('identity_grammar', 'source_identity', limit: 128);
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
