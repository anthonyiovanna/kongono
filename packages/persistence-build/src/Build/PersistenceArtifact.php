<?php

declare(strict_types=1);

namespace Kongono\PersistenceBuild\Build;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\PersistenceBuild\Exception\InvalidPersistenceArtifactException;
use Kongono\PersistenceBuild\Internal\CanonicalJson;
use Kongono\PersistenceBuild\Internal\PersistenceBuildFailureCategory;
use Kongono\PersistenceBuild\Internal\PersistenceModelCompiler;
use Kongono\PersistenceBuild\Model\PersistenceModel;

/** Stores one detached canonical persistence artifact and exact identities. */
#[Api(ApiStatus::Experimental)]
final readonly class PersistenceArtifact
{
    public function __construct(
        private string $canonicalBytes,
        private string $artifactSha256,
        private string $sourceSha256,
        private string $modelSha256,
        private string $mappingArtifactSha256,
        private string $builderImplementationSha256,
        private PersistenceModel $model,
    ) {
        foreach ([$artifactSha256, $sourceSha256, $modelSha256, $mappingArtifactSha256, $builderImplementationSha256] as $digest) {
            if (1 !== preg_match('/^[a-f0-9]{64}$/D', $digest)) {
                throw InvalidPersistenceArtifactException::invalid(PersistenceBuildFailureCategory::ArtifactIdentity);
            }
        }
        if (!hash_equals($artifactSha256, hash('sha256', $canonicalBytes))) {
            throw InvalidPersistenceArtifactException::invalid(PersistenceBuildFailureCategory::ArtifactIdentity);
        }
        $modelBytes = CanonicalJson::encode((new PersistenceModelCompiler())->modelData($model));
        if (!hash_equals($modelSha256, hash('sha256', $modelBytes))) {
            throw InvalidPersistenceArtifactException::invalid(PersistenceBuildFailureCategory::ArtifactIdentity);
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
    public function mappingArtifactSha256(): string
    {
        return $this->mappingArtifactSha256;
    }
    public function builderImplementationSha256(): string
    {
        return $this->builderImplementationSha256;
    }
    public function model(): PersistenceModel
    {
        return $this->model;
    }
}
