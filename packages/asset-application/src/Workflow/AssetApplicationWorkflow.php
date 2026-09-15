<?php

declare(strict_types=1);

namespace Kongono\AssetApplication\Workflow;

use FilesystemIterator;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetAdapterContract\Adapter\AdapterBuildRequest;
use Kongono\AssetAdapterContract\Adapter\AssetBuildAdapter;
use Kongono\AssetAdapterContract\Artifact\AdapterBuildArtifactValidator;
use Kongono\AssetApplication\Definition\AssetApplicationDefinition;
use Kongono\AssetApplication\Exception\AssetAdapterOperationException;
use Kongono\AssetApplication\Exception\AssetApplicationWorkflowException;
use Kongono\AssetApplication\Exception\AssetResolutionOperationException;
use Kongono\AssetApplication\Exception\ExceptionInterface;
use Kongono\AssetApplication\Internal\AssetApplicationPathGuard;
use Kongono\AssetApplication\Source\AssetSourceDefinitionLoader;
use Kongono\AssetBuild\Artifact\AssetBuildArtifactPublisher;
use Kongono\AssetBuild\Artifact\AssetBuildArtifactValidator;
use Kongono\AssetBuild\Build\AssetBuildResult;
use Kongono\AssetBuild\Build\NativeAssetBuilder;
use Kongono\AssetBuild\Source\AssetSourceSet;
use Kongono\AssetContract\Identity\LogicalAssetIdentity;
use Kongono\AssetResolution\Artifact\AssetResolutionArtifact;
use Kongono\AssetResolution\Artifact\AssetResolutionArtifactFactory;
use Kongono\AssetResolution\Policy\AssetResolutionPolicy;
use Kongono\AssetResolution\Resolution\ExactCurrentAssetResolver;
use Kongono\AssetResolution\Resolution\ResolvedAssetSet;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Throwable;

/** Composes explicit native and caller-supplied adapter asset operations. */
#[Api(ApiStatus::Experimental)]
final class AssetApplicationWorkflow
{
    public function plan(AssetApplicationDefinition $definition): PlannedAssetApplicationBuild
    {
        [$canonical, $sourceSet, $result, $resolution] = $this->expected($definition);
        $present = $this->targetPresent($canonical, $sourceSet, $result);

        return $this->planned($sourceSet, $result, $resolution, !$present);
    }

    public function build(AssetApplicationDefinition $definition): CurrentAssetApplicationBuild
    {
        [$canonical, , $result] = $this->expected($definition);
        try {
            $publication = (new AssetBuildArtifactPublisher())->publish($result, $canonical->outputDirectory());
        } catch (Throwable $failure) {
            if ($failure instanceof \Error) {
                throw $failure;
            }
            throw AssetApplicationWorkflowException::failed('publication', $failure);
        }
        if ($publication->directoryName() !== $this->basename($result)) {
            throw AssetApplicationWorkflowException::failed('publication');
        }

        return $this->check($canonical);
    }

    public function check(AssetApplicationDefinition $definition): CurrentAssetApplicationBuild
    {
        return $this->checked($definition)[0];
    }

    public function resolve(AssetApplicationDefinition $definition, LogicalAssetIdentity $identity, AssetResolutionPolicy $policy): ResolvedAssetSet
    {
        try {
            [, $resolution] = $this->checked($definition);

            return (new ExactCurrentAssetResolver())->resolve($resolution, $resolution->identity(), $identity, $policy);
        } catch (Throwable $failure) {
            if ($failure instanceof \Error) {
                throw $failure;
            }
            if ($failure instanceof AssetResolutionOperationException) {
                throw $failure;
            }
            throw AssetResolutionOperationException::failed('lookup', $failure);
        }
    }

    public function buildWithAdapter(AssetBuildAdapter $adapter, AdapterBuildRequest $request): AdapterAssetApplicationBuild
    {
        try {
            $result = $adapter->build($request);
        } catch (Throwable $failure) {
            if ($failure instanceof \Error) {
                throw $failure;
            }
            throw AssetAdapterOperationException::failed('execution', $failure);
        }
        try {
            if (!$result->outputSet()->sourceSet()->equals($request->sourceSet())
                || !$result->artifact()->profileIdentity()->equals($request->profileIdentity())
                || !$result->artifact()->toolchainIdentity()->equals($request->toolchainIdentity())) {
                throw AssetAdapterOperationException::failed('request_relation');
            }
            [$artifact, $set] = (new AdapterBuildArtifactValidator())->validate(
                $request->sourceSet(),
                $request->profileIdentity(),
                $request->toolchainIdentity(),
                $result->artifact()->bytes(),
                $result->outputBytesByPath(),
                $request->limits()->outputDependencyDepth(),
            );
            if (!$artifact->equals($result->artifact())) {
                throw AssetAdapterOperationException::failed('result_validation');
            }
            $resolution = (new AssetResolutionArtifactFactory())->fromAdapter($result);

            return new AdapterAssetApplicationBuild($resolution, $artifact->profileIdentity()->value(), $set->bindingCount(), $set->dependencyCount(), $set->outputCount());
        } catch (Throwable $failure) {
            if ($failure instanceof \Error || $failure instanceof AssetAdapterOperationException) {
                throw $failure;
            }
            throw AssetAdapterOperationException::failed('result_validation', $failure);
        }
    }

    /** @return array{AssetApplicationDefinition,AssetSourceSet,AssetBuildResult,AssetResolutionArtifact} */
    private function expected(AssetApplicationDefinition $definition): array
    {
        try {
            $resolved = AssetApplicationPathGuard::resolve($definition);
            $sourceSet = (new AssetSourceDefinitionLoader())->load($resolved['definition']->applicationRoot(), $resolved['definition_bytes']);
        } catch (Throwable $failure) {
            if ($failure instanceof \Error || $failure instanceof ExceptionInterface) {
                throw $failure;
            }
            throw AssetApplicationWorkflowException::failed('source_read', $failure);
        }
        try {
            $result = (new NativeAssetBuilder())->build($sourceSet);
            $resolution = (new AssetResolutionArtifactFactory())->fromNative($sourceSet, $result);
        } catch (Throwable $failure) {
            if ($failure instanceof \Error) {
                throw $failure;
            }
            throw AssetApplicationWorkflowException::failed('native_build', $failure);
        }

        return [$resolved['definition'], $sourceSet, $result, $resolution];
    }

    /** @return array{CurrentAssetApplicationBuild,AssetResolutionArtifact} */
    private function checked(AssetApplicationDefinition $definition): array
    {
        [$canonical, $sourceSet, $result, $resolution] = $this->expected($definition);
        $this->readCurrent($canonical, $sourceSet, $result);
        $set = $sourceSet->declarationSet();
        $current = new CurrentAssetApplicationBuild($resolution, $this->basename($result), $set->declarationCount(), $set->dependencyCount(), $set->entryPointCount(), $result->outputCount());

        return [$current, $resolution];
    }

    private function targetPresent(AssetApplicationDefinition $definition, AssetSourceSet $sourceSet, AssetBuildResult $result): bool
    {
        $target = $definition->outputDirectory() . '/' . $this->basename($result);
        if (!file_exists($target) && !is_link($target)) {
            return false;
        }
        $this->readCurrent($definition, $sourceSet, $result);

        return true;
    }

    private function readCurrent(AssetApplicationDefinition $definition, AssetSourceSet $sourceSet, AssetBuildResult $expected): void
    {
        $target = $definition->outputDirectory() . '/' . $this->basename($expected);
        $resolved = realpath($target);
        if (false === $resolved || AssetApplicationPathGuard::normalize($resolved) !== $target || is_link($target) || !is_dir($target)) {
            throw AssetApplicationWorkflowException::failed('target_assessment');
        }
        $expectedFiles = ['artifact.json' => $expected->artifact()->bytes(), ...$expected->outputBytesByPath()];
        ksort($expectedFiles, SORT_STRING);
        if (array_keys($expectedFiles) !== $this->inventory($target)) {
            throw AssetApplicationWorkflowException::failed('target_assessment');
        }
        $actualObjects = [];
        foreach ($expectedFiles as $relative => $bytes) {
            $actual = $this->read($target . '/' . $relative, strlen($bytes));
            if (!hash_equals($bytes, $actual)) {
                throw AssetApplicationWorkflowException::failed('current_check');
            }
            if ('artifact.json' !== $relative) {
                $actualObjects[$relative] = $actual;
            }
        }
        try {
            $validated = (new AssetBuildArtifactValidator())->validateCurrent($sourceSet, $expectedFiles['artifact.json'], $actualObjects);
            if (!$validated->equals($expected->artifact())) {
                throw AssetApplicationWorkflowException::failed('current_check');
            }
        } catch (Throwable $failure) {
            if ($failure instanceof \Error || $failure instanceof AssetApplicationWorkflowException) {
                throw $failure;
            }
            throw AssetApplicationWorkflowException::failed('current_check', $failure);
        }
    }

    /** @return list<string> */
    private function inventory(string $target): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($target, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
        foreach ($iterator as $entry) {
            if (!$entry instanceof \SplFileInfo) {
                throw AssetApplicationWorkflowException::failed('target_assessment');
            }
            $path = AssetApplicationPathGuard::normalize($entry->getPathname());
            $real = realpath($path);
            if (is_link($path) || false === $real || AssetApplicationPathGuard::normalize($real) !== $path) {
                throw AssetApplicationWorkflowException::failed('target_assessment');
            }
            if ($entry->isDir()) {
                continue;
            }
            if (!$entry->isFile()) {
                throw AssetApplicationWorkflowException::failed('target_assessment');
            }
            $files[] = substr($path, strlen($target) + 1);
        }
        sort($files, SORT_STRING);

        return $files;
    }

    private function read(string $path, int $expectedBytes): string
    {
        $resolved = realpath($path);
        if (false === $resolved || AssetApplicationPathGuard::normalize($resolved) !== $path || is_link($path) || !is_file($path)) {
            throw AssetApplicationWorkflowException::failed('target_assessment');
        }
        $before = @stat($path);
        if (false === $before || $before['size'] !== $expectedBytes || $expectedBytes > 67_108_864) {
            throw AssetApplicationWorkflowException::failed('target_assessment');
        }
        $bytes = @file_get_contents($path);
        $after = @stat($path);
        if (false === $bytes || strlen($bytes) !== $expectedBytes) {
            throw AssetApplicationWorkflowException::failed('target_assessment');
        }
        foreach (['dev', 'ino', 'mode', 'size', 'mtime', 'ctime'] as $key) {
            if ($before[$key] !== $after[$key]) {
                throw AssetApplicationWorkflowException::failed('target_assessment');
            }
        }

        return $bytes;
    }

    private function planned(AssetSourceSet $sourceSet, AssetBuildResult $result, AssetResolutionArtifact $resolution, bool $publicationRequired): PlannedAssetApplicationBuild
    {
        $artifact = $result->artifact();

        return new PlannedAssetApplicationBuild(
            $artifact->sourceSetIdentity()->value(),
            $artifact->graphIdentity()->value(),
            $artifact->buildIdentity()->value(),
            $resolution->identity()->value(),
            $this->basename($result),
            $sourceSet->declarationSet()->declarationCount(),
            $sourceSet->declarationSet()->dependencyCount(),
            $sourceSet->declarationSet()->entryPointCount(),
            $result->outputCount(),
            $publicationRequired,
        );
    }

    private function basename(AssetBuildResult $result): string
    {
        return 'kongono-assets-v1-' . $result->artifact()->buildIdentity()->value();
    }
}
