<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphArtifact\Identity;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ApplicationGraphArtifact\Exception\InvalidApplicationGraphArtifactException;

/** Holds the content-derived identity of one complete canonical Graph payload. */
#[Api(ApiStatus::Experimental)]
final readonly class GraphIdentity
{
    public function __construct(private string $sha256)
    {
        if (1 !== preg_match('/\A[a-f0-9]{64}\z/D', $sha256)) {
            throw InvalidApplicationGraphArtifactException::artifact('identity_digest_grammar', 'graph');
        }
    }

    public function sha256(): string
    {
        return $this->sha256;
    }

    public function value(): string
    {
        return 'kgn:graph:v1:graph:' . $this->sha256;
    }
}
