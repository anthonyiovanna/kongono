<?php

declare(strict_types=1);

namespace Kongono\StorageApplication\Workflow;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\StorageApplication\Definition\StorageApplicationDefinition;
use Kongono\StorageApplication\Exception\ExceptionInterface;
use Kongono\StorageApplication\Exception\StorageApplicationWorkflowException;
use Kongono\StorageApplication\Internal\StorageApplicationPathGuard;
use Kongono\StorageApplication\Source\StorageProfileSourceLoader;
use Kongono\StorageBuild\Artifact\StorageProfileArtifact;
use Kongono\StorageBuild\Artifact\StorageProfileArtifactPublisher;
use Kongono\StorageBuild\Artifact\StorageProfileArtifactValidator;
use Kongono\StorageBuild\Build\StorageProfileBuilder;
use Kongono\StorageBuild\Build\StorageProfileBuildResult;
use Throwable;

/** Composes explicit plan, publication, and exact-current storage-profile operations. */
#[Api(ApiStatus::Experimental)]
final class StorageApplicationWorkflow
{
    public function plan(StorageApplicationDefinition $definition): PlannedStorageApplicationBuild
    {
        [$canonical, $result] = $this->expected($definition);
        $target = $this->target($canonical, $result->artifact());
        $required = true;
        if (file_exists($target) || is_link($target)) {
            $this->readCurrent($canonical, $result);
            $required = false;
        }
        return $this->planned($result, $required);
    }

    public function build(StorageApplicationDefinition $definition): CurrentStorageApplicationBuild
    {
        [$canonical, $result] = $this->expected($definition);
        try {
            $publication = (new StorageProfileArtifactPublisher())->publish($result->artifact(), $canonical->outputDirectory());
            if ($publication->artifactIdentity()->value() !== $result->artifact()->artifactIdentity()->value()) {
                throw StorageApplicationWorkflowException::publication();
            }
        } catch (Throwable $failure) {
            if ($failure instanceof \Error || $failure instanceof ExceptionInterface) {
                throw $failure;
            }
            throw StorageApplicationWorkflowException::publication($failure);
        }
        return $this->readCurrent($canonical, $result);
    }

    public function check(StorageApplicationDefinition $definition): CurrentStorageApplicationBuild
    {
        [$canonical, $result] = $this->expected($definition);
        return $this->readCurrent($canonical, $result);
    }

    /** @return array{StorageApplicationDefinition,StorageProfileBuildResult} */
    private function expected(StorageApplicationDefinition $definition): array
    {
        try {
            $resolved = StorageApplicationPathGuard::resolve($definition);
            $profiles = (new StorageProfileSourceLoader())->load($resolved['source_bytes']);
            return [$resolved['definition'], (new StorageProfileBuilder())->build($profiles)];
        } catch (Throwable $failure) {
            if ($failure instanceof \Error || $failure instanceof ExceptionInterface) {
                throw $failure;
            }
            throw StorageApplicationWorkflowException::workflow('expected', $failure);
        }
    }

    private function readCurrent(StorageApplicationDefinition $definition, StorageProfileBuildResult $result): CurrentStorageApplicationBuild
    {
        try {
            $bytes = StorageApplicationPathGuard::readArtifact(
                $definition->outputDirectory(),
                $this->target($definition, $result->artifact()),
                1_048_576,
            );
            $artifact = (new StorageProfileArtifactValidator())->validateCurrent($result->profiles(), $bytes);
            if (!$artifact->equals($result->artifact())) {
                throw StorageApplicationWorkflowException::workflow('currentness');
            }
            return new CurrentStorageApplicationBuild($result->profiles(), $artifact);
        } catch (Throwable $failure) {
            if ($failure instanceof \Error || $failure instanceof StorageApplicationWorkflowException) {
                throw $failure;
            }
            throw StorageApplicationWorkflowException::workflow('currentness', $failure);
        }
    }

    private function planned(StorageProfileBuildResult $result, bool $required): PlannedStorageApplicationBuild
    {
        return new PlannedStorageApplicationBuild(
            $result->profiles()->identity()->value(),
            $result->buildIdentity()->value(),
            $result->artifact()->artifactIdentity()->value(),
            $result->profiles()->profileCount(),
            $required,
        );
    }

    private function target(StorageApplicationDefinition $definition, StorageProfileArtifact $artifact): string
    {
        $digest = $artifact->artifactIdentity()->value();
        return $definition->outputDirectory() . '/objects/sha256/' . substr($digest, 0, 2) . '/' . $digest . '/storage-profile.json';
    }
}
