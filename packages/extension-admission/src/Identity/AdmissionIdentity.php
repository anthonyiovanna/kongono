<?php

declare(strict_types=1);

namespace Kongono\ExtensionAdmission\Identity;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExtensionAdmission\Exception\InvalidExtensionAdmissionException;

/** Holds the exact lowercase SHA-256 identity of one admitted extension input. */
#[Api(ApiStatus::Experimental)]
final readonly class AdmissionIdentity
{
    public function __construct(private string $sha256)
    {
        if (1 !== preg_match('/\A[a-f0-9]{64}\z/D', $sha256)) {
            throw InvalidExtensionAdmissionException::invalid('admission_identity_grammar', 'identity');
        }
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
