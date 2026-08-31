<?php

declare(strict_types=1);

namespace Kongono\ExtensionBuild\Identity;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExtensionBuild\Exception\InvalidExtensionBuildException;

/** Holds the exact lowercase SHA-256 identity of one canonical declaration set. */
#[Api(ApiStatus::Experimental)]
final readonly class DeclarationSetIdentity
{
    private string $sha256;

    public function __construct(string $sha256)
    {
        if (1 !== preg_match('/\A[a-f0-9]{64}\z/D', $sha256)) {
            throw InvalidExtensionBuildException::input('declaration_set_identity_grammar', 'identity');
        }

        $this->sha256 = $sha256;
    }

    public function sha256(): string
    {
        return $this->sha256;
    }

    public function value(): string
    {
        return $this->sha256;
    }

    public function equals(self $other): bool
    {
        return hash_equals($this->sha256, $other->sha256);
    }
}
