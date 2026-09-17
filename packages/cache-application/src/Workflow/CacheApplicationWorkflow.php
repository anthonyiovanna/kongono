<?php

declare(strict_types=1);

namespace Kongono\CacheApplication\Workflow;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\CacheApplication\Definition\CacheApplicationDefinition;
use Kongono\CacheApplication\Exception\CacheApplicationWorkflowException;
use Kongono\CacheApplication\Exception\ExceptionInterface;
use Kongono\CacheApplication\Internal\CacheApplicationPathGuard;
use Kongono\CacheApplication\Source\CacheProfileSourceLoader;
use Kongono\CacheBuild\Artifact\CacheProfileArtifact;
use Kongono\CacheBuild\Artifact\CacheProfileArtifactPublisher;
use Kongono\CacheBuild\Artifact\CacheProfileArtifactValidator;
use Kongono\CacheBuild\Build\CacheProfileBuilder;
use Kongono\CacheBuild\Build\CacheProfileBuildResult;
use Throwable;

/** Composes explicit plan, publication, and exact-current cache-profile operations. */
#[Api(ApiStatus::Experimental)]
final class CacheApplicationWorkflow
{
    public function plan(CacheApplicationDefinition $definition): PlannedCacheApplicationBuild
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

    public function build(CacheApplicationDefinition $definition): CurrentCacheApplicationBuild
    {
        [$canonical, $result] = $this->expected($definition);
        try {
            $publication = (new CacheProfileArtifactPublisher())->publish($result->artifact(), $canonical->outputDirectory());
            if ($publication->artifactIdentity()->value() !== $result->artifact()->artifactIdentity()->value()) {
                throw CacheApplicationWorkflowException::publication();
            }
        } catch (Throwable $failure) {
            if ($failure instanceof \Error || $failure instanceof ExceptionInterface) {
                throw $failure;
            }
            throw CacheApplicationWorkflowException::publication($failure);
        }
        return $this->readCurrent($canonical, $result);
    }

    public function check(CacheApplicationDefinition $definition): CurrentCacheApplicationBuild
    {
        [$canonical, $result] = $this->expected($definition);
        return $this->readCurrent($canonical, $result);
    }

    /** @return array{CacheApplicationDefinition,CacheProfileBuildResult} */
    private function expected(CacheApplicationDefinition $definition): array
    {
        try {
            $resolved = CacheApplicationPathGuard::resolve($definition);
            $profiles = (new CacheProfileSourceLoader())->load($resolved['source_bytes']);
            return [$resolved['definition'], (new CacheProfileBuilder())->build($profiles)];
        } catch (Throwable $failure) {
            if ($failure instanceof \Error || $failure instanceof ExceptionInterface) {
                throw $failure;
            }
            throw CacheApplicationWorkflowException::workflow('expected', $failure);
        }
    }

    private function readCurrent(CacheApplicationDefinition $definition, CacheProfileBuildResult $result): CurrentCacheApplicationBuild
    {
        try {
            $bytes = CacheApplicationPathGuard::readArtifact(
                $definition->outputDirectory(),
                $this->target($definition, $result->artifact()),
                1_048_576,
            );
            $artifact = (new CacheProfileArtifactValidator())->validateCurrent($result->profiles(), $bytes);
            if (!$artifact->equals($result->artifact())) {
                throw CacheApplicationWorkflowException::workflow('currentness');
            }
            return new CurrentCacheApplicationBuild($result->profiles(), $artifact);
        } catch (Throwable $failure) {
            if ($failure instanceof \Error || $failure instanceof CacheApplicationWorkflowException) {
                throw $failure;
            }
            throw CacheApplicationWorkflowException::workflow('currentness', $failure);
        }
    }

    private function planned(CacheProfileBuildResult $result, bool $required): PlannedCacheApplicationBuild
    {
        return new PlannedCacheApplicationBuild(
            $result->profiles()->identity()->value(),
            $result->buildIdentity()->value(),
            $result->artifact()->artifactIdentity()->value(),
            $result->profiles()->profileCount(),
            $required,
        );
    }

    private function target(CacheApplicationDefinition $definition, CacheProfileArtifact $artifact): string
    {
        $digest = $artifact->artifactIdentity()->value();
        return $definition->outputDirectory() . '/objects/sha256/' . substr($digest, 0, 2) . '/' . $digest . '/cache-profile.json';
    }
}
