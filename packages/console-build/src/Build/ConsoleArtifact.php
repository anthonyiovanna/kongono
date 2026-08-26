<?php

declare(strict_types=1);

namespace Kongono\ConsoleBuild\Build;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ConsoleBuild\Exception\InvalidConsoleArtifactException;
use Kongono\ConsoleBuild\Internal\CanonicalJson;
use Kongono\ConsoleBuild\Internal\ConsoleBuildFailureCategory;
use Kongono\ConsoleBuild\Internal\ConsoleModelCompiler;
use Kongono\ConsoleBuild\Model\ConsoleModel;

/** Stores one detached canonical command artifact and exact identities. */
#[Api(ApiStatus::Experimental)]
final readonly class ConsoleArtifact
{
    public function __construct(
        private string $canonicalBytes,
        private string $artifactSha256,
        private string $sourceSha256,
        private string $modelSha256,
        private string $builderImplementationSha256,
        private ConsoleModel $model,
    ) {
        foreach ([$artifactSha256, $sourceSha256, $modelSha256, $builderImplementationSha256] as $digest) {
            if (1 !== preg_match('/^[a-f0-9]{64}$/D', $digest)) {
                throw InvalidConsoleArtifactException::invalid(ConsoleBuildFailureCategory::ArtifactIdentity);
            }
        }
        if (!hash_equals($artifactSha256, hash('sha256', $canonicalBytes))) {
            throw InvalidConsoleArtifactException::invalid(ConsoleBuildFailureCategory::ArtifactIdentity);
        }
        $modelBytes = CanonicalJson::encode((new ConsoleModelCompiler())->modelData($model));
        if (!hash_equals($modelSha256, hash('sha256', $modelBytes))) {
            throw InvalidConsoleArtifactException::invalid(ConsoleBuildFailureCategory::ArtifactIdentity);
        }
    }

    public function canonicalBytes(): string
    {
        return $this->canonicalBytes;
    }

    public function artifactSha256(): string
    {
        return $this->artifactSha256;
    }

    public function sourceSha256(): string
    {
        return $this->sourceSha256;
    }

    public function modelSha256(): string
    {
        return $this->modelSha256;
    }

    public function builderImplementationSha256(): string
    {
        return $this->builderImplementationSha256;
    }

    public function model(): ConsoleModel
    {
        return $this->model;
    }
}
