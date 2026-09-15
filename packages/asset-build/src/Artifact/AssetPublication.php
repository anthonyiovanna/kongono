<?php

declare(strict_types=1);

namespace Kongono\AssetBuild\Artifact;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetBuild\Build\AssetBuildIdentity;

/** Carries relative publication evidence without exposing the ambient root. */
#[Api(ApiStatus::Experimental)]
final readonly class AssetPublication
{
    /** @var list<string> */
    private array $outputPaths;

    /** @param list<string> $outputPaths */
    public function __construct(private AssetBuildIdentity $buildIdentity, private string $directoryName, array $outputPaths)
    {
        $this->outputPaths = [...$outputPaths];
    }

    public function buildIdentity(): AssetBuildIdentity
    {
        return $this->buildIdentity;
    }
    public function directoryName(): string
    {
        return $this->directoryName;
    }
    public function artifactPath(): string
    {
        return 'artifact.json';
    }
    /** @return list<string> */
    public function outputPaths(): array
    {
        return [...$this->outputPaths];
    }
}
