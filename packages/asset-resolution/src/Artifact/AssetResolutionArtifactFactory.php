<?php

declare(strict_types=1);

namespace Kongono\AssetResolution\Artifact;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetAdapterContract\Adapter\AdapterBuildResult;
use Kongono\AssetAdapterContract\Artifact\AdapterBuildArtifactValidator;
use Kongono\AssetAdapterContract\Identity\AdapterOutputIdentity;
use Kongono\AssetAdapterContract\Output\AdaptedAssetOutput;
use Kongono\AssetAdapterContract\Output\AdapterOutputDependency;
use Kongono\AssetBuild\Artifact\AssetBuildArtifactValidator;
use Kongono\AssetBuild\Build\AssetBuildResult;
use Kongono\AssetBuild\Build\AssetOutput;
use Kongono\AssetBuild\Graph\AssetGraphBuilder;
use Kongono\AssetBuild\Source\AssetSourceSet;
use Kongono\AssetContract\Dependency\AssetDependency;
use Kongono\AssetContract\Dependency\AssetDependencyKind;
use Kongono\AssetResolution\ContractVersion;
use Kongono\AssetResolution\Exception\InvalidAssetResolutionException;
use Throwable;

/** Revalidates complete build evidence and creates normalized resolution metadata. */
#[Api(ApiStatus::Experimental)]
final class AssetResolutionArtifactFactory
{
    public function fromNative(AssetSourceSet $currentSourceSet, AssetBuildResult $result): AssetResolutionArtifact
    {
        try {
            $validated = (new AssetBuildArtifactValidator())->validateCurrent($currentSourceSet, $result->artifact()->bytes(), $result->outputBytesByPath());
            if (!$validated->equals($result->artifact())) {
                throw InvalidAssetResolutionException::invalidArtifact('native_artifact_relation');
            }
            $outputs = [];
            foreach ($result->outputs() as $output) {
                $outputs[$output->identity()->value()] = $output;
            }
            $dependencies = [];
            foreach ($currentSourceSet->declarationSet()->dependencies() as $dependency) {
                $dependencies[$dependency->source()->value()][] = $dependency;
            }
            $entries = [];
            foreach ($currentSourceSet->declarationSet()->declarations() as $declaration) {
                $logical = $declaration->identity()->value();
                if (!isset($outputs[$logical])) {
                    throw InvalidAssetResolutionException::invalidArtifact('missing_native_output');
                }
                $entries[] = new AssetResolutionEntry($declaration->identity(), self::nativeObject($outputs[$logical]), self::nativeClosure($logical, $outputs, $dependencies));
            }
            $graph = (new AssetGraphBuilder())->build($currentSourceSet);

            return new AssetResolutionArtifact(ContractVersion::Version1, 'native', $validated->buildIdentity()->value(), $currentSourceSet->identity()->value(), $graph->identity()->value(), $entries);
        } catch (Throwable $failure) {
            if ($failure instanceof InvalidAssetResolutionException) {
                throw $failure;
            }
            throw InvalidAssetResolutionException::invalidArtifact('native_validation', previous: $failure);
        }
    }

    public function fromAdapter(AdapterBuildResult $result): AssetResolutionArtifact
    {
        try {
            $supplied = $result->outputSet();
            [$artifact, $set] = (new AdapterBuildArtifactValidator())->validate($supplied->sourceSet(), $result->artifact()->profileIdentity(), $result->artifact()->toolchainIdentity(), $result->artifact()->bytes(), $result->outputBytesByPath(), $supplied->maximumDepth());
            if (!$artifact->equals($result->artifact()) || $set->outputCount() !== $supplied->outputCount() || $set->bindingCount() !== $supplied->bindingCount() || $set->dependencyCount() !== $supplied->dependencyCount()) {
                throw InvalidAssetResolutionException::invalidArtifact('adapter_artifact_relation');
            }
            $outputs = [];
            foreach ($set->outputs() as $output) {
                $outputs[$output->identity()->value()] = $output;
            }
            $dependencies = [];
            foreach ($set->dependencies() as $dependency) {
                $dependencies[$dependency->source()->value()][] = $dependency;
            }
            $entries = [];
            foreach ($set->entryBindings() as $binding) {
                $output = $outputs[$binding->output()->value()] ?? null;
                if (null === $output) {
                    throw InvalidAssetResolutionException::invalidArtifact('missing_adapter_output');
                }
                $entries[] = new AssetResolutionEntry($binding->entryPoint(), self::adapterObject($output), self::adapterClosure($binding->output(), $outputs, $dependencies));
            }

            return new AssetResolutionArtifact(ContractVersion::Version1, 'adapter', $artifact->buildIdentity()->value(), $artifact->sourceSetIdentity()->value(), $artifact->graphIdentity()->value(), $entries);
        } catch (Throwable $failure) {
            if ($failure instanceof InvalidAssetResolutionException) {
                throw $failure;
            }
            throw InvalidAssetResolutionException::invalidArtifact('adapter_validation', previous: $failure);
        }
    }

    private static function nativeObject(AssetOutput $output): AssetResolutionObject
    {
        return new AssetResolutionObject($output->path()->value(), $output->kind(), $output->contentType(), $output->contentIdentity(), $output->byteCount(), $output->integrity(), $output->preloadHint());
    }

    private static function adapterObject(AdaptedAssetOutput $output): AssetResolutionObject
    {
        return new AssetResolutionObject($output->path()->value(), $output->kind(), $output->contentType(), $output->contentIdentity(), $output->byteCount(), $output->integrity(), $output->preloadHint());
    }

    /**
     * @param array<string, AssetOutput> $outputs
     * @param array<string, list<AssetDependency>> $dependencies
     * @return list<array{kind: AssetDependencyKind, object: AssetResolutionObject}>
     */
    private static function nativeClosure(string $root, array $outputs, array $dependencies): array
    {
        $result = [];
        $seen = [];
        $pending = $dependencies[$root] ?? [];
        while ([] !== $pending) {
            /** @var AssetDependency $dependency */
            $dependency = array_shift($pending);
            $target = $dependency->target()->value();
            if (isset($seen[$target])) {
                continue;
            }
            $output = $outputs[$target] ?? null;
            if (null === $output) {
                throw InvalidAssetResolutionException::invalidArtifact('missing_native_dependency');
            }
            $seen[$target] = true;
            $result[] = ['kind' => $dependency->kind(), 'object' => self::nativeObject($output)];
            foreach ($dependencies[$target] ?? [] as $next) {
                $pending[] = $next;
            }
        }

        return $result;
    }

    /**
     * @param array<string, AdaptedAssetOutput> $outputs
     * @param array<string, list<AdapterOutputDependency>> $dependencies
     * @return list<array{kind: AssetDependencyKind, object: AssetResolutionObject}>
     */
    private static function adapterClosure(AdapterOutputIdentity $root, array $outputs, array $dependencies): array
    {
        $result = [];
        $seen = [];
        $pending = $dependencies[$root->value()] ?? [];
        while ([] !== $pending) {
            /** @var AdapterOutputDependency $dependency */
            $dependency = array_shift($pending);
            $target = $dependency->target()->value();
            if (isset($seen[$target])) {
                continue;
            }
            $output = $outputs[$target] ?? null;
            if (null === $output) {
                throw InvalidAssetResolutionException::invalidArtifact('missing_adapter_dependency');
            }
            $seen[$target] = true;
            $result[] = ['kind' => $dependency->kind(), 'object' => self::adapterObject($output)];
            foreach ($dependencies[$target] ?? [] as $next) {
                $pending[] = $next;
            }
        }

        return $result;
    }
}
