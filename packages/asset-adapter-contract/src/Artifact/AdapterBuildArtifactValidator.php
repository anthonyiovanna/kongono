<?php

declare(strict_types=1);

namespace Kongono\AssetAdapterContract\Artifact;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetAdapterContract\Exception\InvalidAdapterBuildArtifactException;
use Kongono\AssetAdapterContract\Identity\AdapterBuildIdentity;
use Kongono\AssetAdapterContract\Identity\AdapterOutputIdentity;
use Kongono\AssetAdapterContract\Identity\AdapterProfileIdentity;
use Kongono\AssetAdapterContract\Identity\ToolchainIdentity;
use Kongono\AssetAdapterContract\Internal\AdapterContractGuard;
use Kongono\AssetAdapterContract\Internal\CanonicalJson;
use Kongono\AssetAdapterContract\Output\AdaptedAssetOutput;
use Kongono\AssetAdapterContract\Output\AdapterEntryBinding;
use Kongono\AssetAdapterContract\Output\AdapterOutputDependency;
use Kongono\AssetAdapterContract\Output\AdapterOutputSet;
use Kongono\AssetBuild\Build\AssetOutputPath;
use Kongono\AssetBuild\Graph\AssetGraphBuilder;
use Kongono\AssetBuild\Source\AssetSourceSet;
use Kongono\AssetContract\Asset\AssetContentType;
use Kongono\AssetContract\Asset\AssetKind;
use Kongono\AssetContract\Delivery\AssetIntegrity;
use Kongono\AssetContract\Delivery\IntegrityAlgorithm;
use Kongono\AssetContract\Delivery\PreloadHint;
use Kongono\AssetContract\Dependency\AssetDependencyKind;
use Kongono\AssetContract\Identity\ContentIdentity;
use Kongono\AssetContract\Identity\LogicalAssetIdentity;
use Throwable;

/** Strictly validates canonical adapter artifacts against explicit current evidence. */
#[Api(ApiStatus::Experimental)]
final class AdapterBuildArtifactValidator
{
    /**
     * @param array<array-key, mixed> $outputBytesByPath
     *
     * @return array{0: AdapterBuildArtifact, 1: AdapterOutputSet}
     */
    public function validate(AssetSourceSet $sourceSet, AdapterProfileIdentity $profile, ToolchainIdentity $toolchain, string $artifactBytes, array $outputBytesByPath, int $depthLimit = 256): array
    {
        try {
            $model = CanonicalJson::decode($artifactBytes);
            if (!hash_equals(CanonicalJson::encode($model), $artifactBytes)) {
                throw InvalidAdapterBuildArtifactException::invalid('artifact_canonical');
            }
            AdapterContractGuard::exactKeys($model, ['schema','artifact_version','contract_version','adapter','toolchain_sha256','source_set_sha256','graph_sha256','entry_bindings','output_dependencies','outputs']);
            if ('kongono.asset-adapter-build-artifact' !== $model['schema'] || 1 !== $model['artifact_version'] || 1 !== $model['contract_version']) {
                throw InvalidAdapterBuildArtifactException::invalid('artifact_schema');
            }
            $adapter = $this->object($model['adapter']);
            AdapterContractGuard::exactKeys($adapter, ['package','profile','version']);
            if (!is_string($adapter['package']) || !is_string($adapter['profile']) || !is_int($adapter['version'])) {
                throw InvalidAdapterBuildArtifactException::invalid('artifact_schema');
            }
            $artifactProfile = new AdapterProfileIdentity($adapter['profile']);
            $artifactToolchain = new ToolchainIdentity($this->string($model['toolchain_sha256']));
            $graph = (new AssetGraphBuilder())->build($sourceSet);
            if (!$artifactProfile->equals($profile) || !$artifactToolchain->equals($toolchain) || $this->string($model['source_set_sha256']) !== $sourceSet->identity()->value() || $this->string($model['graph_sha256']) !== $graph->identity()->value()) {
                throw InvalidAdapterBuildArtifactException::invalid('artifact_currentness');
            }
            $map = AdapterContractGuard::exactStringMap($outputBytesByPath);
            $outputs = [];
            foreach ($this->list($model['outputs']) as $row) {
                $row = $this->object($row);
                AdapterContractGuard::exactKeys($row, ['output_sha256','kind','content_type','content_sha256','byte_count','integrity_sha384','preload_hint','path']);
                $path = new AssetOutputPath($this->string($row['path']));
                $bytes = $map[$path->value()] ?? null;
                if (!is_string($bytes) || !is_int($row['byte_count'])) {
                    throw InvalidAdapterBuildArtifactException::invalid('output_map_relation');
                }$outputs[] = new AdaptedAssetOutput(new AdapterOutputIdentity($this->string($row['output_sha256'])), AssetKind::from($this->string($row['kind'])), AssetContentType::from($this->string($row['content_type'])), new ContentIdentity($this->string($row['content_sha256'])), $row['byte_count'], new AssetIntegrity(IntegrityAlgorithm::Sha384, $this->string($row['integrity_sha384'])), PreloadHint::from($this->string($row['preload_hint'])), $path, $bytes);
            }
            $bindings = [];
            foreach ($this->list($model['entry_bindings']) as $row) {
                $row = $this->object($row);
                AdapterContractGuard::exactKeys($row, ['entry_point','output_sha256']);
                $bindings[] = new AdapterEntryBinding(new LogicalAssetIdentity($this->string($row['entry_point'])), new AdapterOutputIdentity($this->string($row['output_sha256'])));
            }
            $dependencies = [];
            foreach ($this->list($model['output_dependencies']) as $row) {
                $row = $this->object($row);
                AdapterContractGuard::exactKeys($row, ['source_output_sha256','target_output_sha256','kind']);
                $dependencies[] = new AdapterOutputDependency(new AdapterOutputIdentity($this->string($row['source_output_sha256'])), new AdapterOutputIdentity($this->string($row['target_output_sha256'])), AssetDependencyKind::from($this->string($row['kind'])));
            }
            $set = new AdapterOutputSet($sourceSet, $outputs, $bindings, $dependencies, $depthLimit);
            $expected = [];
            foreach ($set->outputs() as $output) {
                $path = $output->path()->value();
                if (isset($expected[$path]) && !hash_equals($expected[$path], $output->bytes())) {
                    throw InvalidAdapterBuildArtifactException::invalid('output_map_relation');
                }$expected[$path] = $output->bytes();
            }ksort($expected, SORT_STRING);
            if ($expected !== $map) {
                throw InvalidAdapterBuildArtifactException::invalid('output_map_relation');
            }
            $artifact = new AdapterBuildArtifact($artifactBytes, AdapterBuildIdentity::fromArtifactBytes($artifactBytes), $sourceSet->identity(), $graph->identity(), $profile, $toolchain, $adapter['package'], $adapter['version'], $set->bindingCount(), $set->dependencyCount(), $set->outputCount());
            return [$artifact, $set];
        } catch (Throwable $failure) {
            if ($failure instanceof \Error || ($failure instanceof InvalidAdapterBuildArtifactException && 'KGN-ASSET-ADAPTER-1924' === $failure->diagnosticId())) {
                throw $failure;
            }throw InvalidAdapterBuildArtifactException::invalid('artifact_schema', $failure);
        }
    }
    public function create(AssetSourceSet $sourceSet, AdapterProfileIdentity $profile, ToolchainIdentity $toolchain, string $adapterPackage, AdapterOutputSet $set, int $depthLimit = 256): AdapterBuildArtifact
    {
        $graph = (new AssetGraphBuilder())->build($sourceSet);
        $bindings = [];
        foreach ($set->entryBindings() as $binding) {
            $bindings[] = ['entry_point' => $binding->entryPoint()->value(),'output_sha256' => $binding->output()->value()];
        }$dependencies = [];
        foreach ($set->dependencies() as $dependency) {
            $dependencies[] = ['source_output_sha256' => $dependency->source()->value(),'target_output_sha256' => $dependency->target()->value(),'kind' => $dependency->kind()->value];
        }$outputs = [];
        $map = [];
        foreach ($set->outputs() as $output) {
            $outputs[] = ['output_sha256' => $output->identity()->value(),'kind' => $output->kind()->value,'content_type' => $output->contentType()->value,'content_sha256' => $output->contentIdentity()->value(),'byte_count' => $output->byteCount(),'integrity_sha384' => $output->integrity()->digest(),'preload_hint' => $output->preloadHint()->value,'path' => $output->path()->value()];
            $map[$output->path()->value()] = $output->bytes();
        }ksort($map, SORT_STRING);
        $bytes = CanonicalJson::encode(['schema' => 'kongono.asset-adapter-build-artifact','artifact_version' => 1,'contract_version' => 1,'adapter' => ['package' => $adapterPackage,'profile' => $profile->value(),'version' => 1],'toolchain_sha256' => $toolchain->value(),'source_set_sha256' => $sourceSet->identity()->value(),'graph_sha256' => $graph->identity()->value(),'entry_bindings' => $bindings,'output_dependencies' => $dependencies,'outputs' => $outputs]);
        [$artifact] = $this->validate($sourceSet, $profile, $toolchain, $bytes, $map, $depthLimit);

        return $artifact;
    }
    /** @return array<string, mixed> */
    private function object(mixed $value): array
    {
        if (!is_array($value) || array_is_list($value)) {
            throw InvalidAdapterBuildArtifactException::invalid('artifact_schema');
        }
        $result = [];
        foreach ($value as $key => $entry) {
            if (!is_string($key)) {
                throw InvalidAdapterBuildArtifactException::invalid('artifact_schema');
            }
            $result[$key] = $entry;
        }

        return $result;
    }

    /** @return list<mixed> */
    private function list(mixed $value): array
    {
        if (!is_array($value) || !array_is_list($value)) {
            throw InvalidAdapterBuildArtifactException::invalid('artifact_schema');
        }

        return $value;
    }

    private function string(mixed $value): string
    {
        if (!is_string($value)) {
            throw InvalidAdapterBuildArtifactException::invalid('artifact_schema');
        }

        return $value;
    }
}
