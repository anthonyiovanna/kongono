<?php

declare(strict_types=1);

namespace Kongono\AssetBuild\Build;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetBuild\Exception\InvalidAssetBuildArtifactException;
use Kongono\AssetContract\Identity\ContentIdentity;

/** Carries one exact relative content-addressed native output object path. */
#[Api(ApiStatus::Experimental)]
final readonly class AssetOutputPath
{
    private string $digest;

    public function __construct(private string $value)
    {
        if (1 !== preg_match('/\Aobjects\/sha256\/([a-f0-9]{2})\/([a-f0-9]{64})\z/D', $value, $match) || $match[1] !== substr($match[2], 0, 2)) {
            throw InvalidAssetBuildArtifactException::output('output_path_grammar');
        }
        $this->digest = $match[2];
    }

    public static function fromContentIdentity(ContentIdentity $identity): self
    {
        $digest = $identity->value();

        return new self('objects/sha256/' . substr($digest, 0, 2) . '/' . $digest);
    }

    public function value(): string
    {
        return $this->value;
    }
    public function digest(): string
    {
        return $this->digest;
    }
    public function matches(ContentIdentity $identity): bool
    {
        return $this->digest === $identity->value();
    }
    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
