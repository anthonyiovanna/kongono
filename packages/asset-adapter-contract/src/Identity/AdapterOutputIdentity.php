<?php

declare(strict_types=1);

namespace Kongono\AssetAdapterContract\Identity;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetAdapterContract\Internal\AdapterContractGuard;

/** Carries an opaque generated-output identity distinct from application logical identity. */
#[Api(ApiStatus::Experimental)]
final readonly class AdapterOutputIdentity
{
    public function __construct(private string $value)
    {
        AdapterContractGuard::sha256($value, 'output_identity_grammar');
    }
    public static function fromProducerKey(AdapterProfileIdentity $profile, string $privateProducerKey): self
    {
        if ('' === $privateProducerKey || strlen($privateProducerKey) > 4096 || str_contains($privateProducerKey, "\0")) {
            throw \Kongono\AssetAdapterContract\Exception\InvalidAssetAdapterContractException::output('producer_key_grammar');
        }
        return new self(hash('sha256', $profile->value() . "\0" . $privateProducerKey));
    }
    public function value(): string
    {
        return $this->value;
    }
    public function equals(self $other): bool
    {
        return hash_equals($this->value, $other->value);
    }
}
