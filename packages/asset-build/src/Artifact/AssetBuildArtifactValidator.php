<?php

declare(strict_types=1);

namespace Kongono\AssetBuild\Artifact;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetBuild\Build\AssetBuildIdentity;
use Kongono\AssetBuild\Build\AssetOutput;
use Kongono\AssetBuild\Build\AssetOutputPath;
use Kongono\AssetBuild\Build\NativeAssetBuilder;
use Kongono\AssetBuild\Build\NativeBuildProfile;
use Kongono\AssetBuild\Exception\InvalidAssetBuildArtifactException;
use Kongono\AssetBuild\Exception\StaleAssetBuildArtifactException;
use Kongono\AssetBuild\Graph\AssetGraphBuilder;
use Kongono\AssetBuild\Internal\BuildGuard;
use Kongono\AssetBuild\Internal\CanonicalJson;
use Kongono\AssetBuild\Source\AssetSource;
use Kongono\AssetBuild\Source\AssetSourcePath;
use Kongono\AssetBuild\Source\AssetSourceSet;
use Kongono\AssetBuild\Source\AssetSourceSetIdentity;
use Kongono\AssetContract\Asset\AssetContentType;
use Kongono\AssetContract\Asset\AssetDeclaration;
use Kongono\AssetContract\Asset\AssetDeclarationSet;
use Kongono\AssetContract\Asset\AssetKind;
use Kongono\AssetContract\ContractVersion;
use Kongono\AssetContract\Delivery\AssetIntegrity;
use Kongono\AssetContract\Delivery\IntegrityAlgorithm;
use Kongono\AssetContract\Delivery\PreloadHint;
use Kongono\AssetContract\Dependency\AssetDependency;
use Kongono\AssetContract\Dependency\AssetDependencyKind;
use Kongono\AssetContract\EntryPoint\AssetEntryPoint;
use Kongono\AssetContract\Identity\ContentIdentity;
use Kongono\AssetContract\Identity\LogicalAssetIdentity;
use Throwable;

/** Strictly validates canonical artifact structure and exact supplied currentness. */
#[Api(ApiStatus::Experimental)]
final class AssetBuildArtifactValidator
{
    /** @param array<array-key, mixed> $outputBytesByPath */
    public function validateStructure(string $artifactBytes, array $outputBytesByPath): AssetBuildArtifact
    {
        try {
            $model = CanonicalJson::decode($artifactBytes);
            if (!hash_equals(CanonicalJson::encode($model), $artifactBytes)) {
                throw InvalidAssetBuildArtifactException::invalid('artifact_canonical');
            }
            BuildGuard::exactKeys($model, ['schema', 'artifact_version', 'contract_version', 'builder', 'source_set_sha256', 'graph_sha256', 'declarations', 'dependencies', 'entry_points', 'outputs']);
            if ('kongono.asset-build-artifact' !== $model['schema'] || 1 !== $model['artifact_version'] || 1 !== $model['contract_version']) {
                throw InvalidAssetBuildArtifactException::invalid('artifact_schema');
            }
            $builder = $this->object($model['builder']);
            BuildGuard::exactKeys($builder, ['package', 'profile', 'version']);
            if ('kongono/asset-build' !== $builder['package'] || NativeBuildProfile::NativeCopyV1->value !== $builder['profile'] || 1 !== $builder['version']) {
                throw InvalidAssetBuildArtifactException::invalid('artifact_schema');
            }
            $sourceSetIdentity = new AssetSourceSetIdentity($this->string($model['source_set_sha256']));
            $graphIdentityValue = BuildGuard::lowercaseSha256($this->string($model['graph_sha256']));
            $declarations = $this->declarations($model['declarations']);
            $dependencies = $this->dependencies($model['dependencies']);
            $entryPoints = $this->entryPoints($model['entry_points']);
            $set = new AssetDeclarationSet(ContractVersion::Version1, $declarations, $dependencies, $entryPoints);
            $placeholderSources = array_map(static fn(AssetDeclaration $declaration): AssetSource => new AssetSource($declaration->identity(), new AssetSourcePath('artifact/' . $declaration->identity()->value()), ''), $set->declarations());
            $graph = (new AssetGraphBuilder())->build(new AssetSourceSet($set, $placeholderSources));
            if (!hash_equals($graphIdentityValue, $graph->identity()->value())) {
                throw InvalidAssetBuildArtifactException::invalid('graph_identity_relation');
            }
            $map = BuildGuard::exactStringMap($outputBytesByPath);
            $outputs = $this->outputs($model['outputs'], $set, $map);
            if (count($outputs) !== $set->declarationCount()) {
                throw InvalidAssetBuildArtifactException::invalid('output_evidence_relation');
            }
            $usedPaths = [];
            foreach ($outputs as $output) {
                $usedPaths[$output->path()->value()] = true;
            }
            $expectedPaths = array_keys($usedPaths);
            sort($expectedPaths, SORT_STRING);
            if ($expectedPaths !== array_keys($map)) {
                throw InvalidAssetBuildArtifactException::invalid('output_evidence_relation');
            }

            return new AssetBuildArtifact(
                $artifactBytes,
                AssetBuildIdentity::fromArtifactBytes($artifactBytes),
                $sourceSetIdentity,
                $graph->identity(),
                NativeBuildProfile::NativeCopyV1,
                $set->declarationCount(),
                $set->dependencyCount(),
                $set->entryPointCount(),
                count($outputs),
            );
        } catch (Throwable $failure) {
            if ($failure instanceof \Error || ($failure instanceof InvalidAssetBuildArtifactException && 'KGN-ASSET-BUILD-1914' === $failure->diagnosticId())) {
                throw $failure;
            }
            throw InvalidAssetBuildArtifactException::invalid('artifact_schema', previous: $failure);
        }
    }

    /** @param array<array-key, mixed> $outputBytesByPath */
    public function validateCurrent(AssetSourceSet $currentSourceSet, string $artifactBytes, array $outputBytesByPath): AssetBuildArtifact
    {
        $artifact = $this->validateStructure($artifactBytes, $outputBytesByPath);
        $current = (new NativeAssetBuilder())->build($currentSourceSet);
        if (!hash_equals($current->artifact()->bytes(), $artifactBytes) || $current->outputBytesByPath() !== BuildGuard::exactStringMap($outputBytesByPath)) {
            throw StaleAssetBuildArtifactException::stale();
        }

        return $artifact;
    }

    /** @return list<AssetDeclaration> */
    private function declarations(mixed $value): array
    {
        $rows = $this->list($value);
        $result = [];
        foreach ($rows as $row) {
            $item = $this->object($row);
            BuildGuard::exactKeys($item, ['identity', 'kind', 'content_type']);
            $kind = AssetKind::tryFrom($this->string($item['kind']));
            $type = AssetContentType::tryFrom($this->string($item['content_type']));
            if (null === $kind || null === $type) {
                throw InvalidAssetBuildArtifactException::invalid('artifact_schema');
            }
            $result[] = new AssetDeclaration(ContractVersion::Version1, new LogicalAssetIdentity($this->string($item['identity'])), $kind, $type);
        }

        return $result;
    }

    /** @return list<AssetDependency> */
    private function dependencies(mixed $value): array
    {
        $rows = $this->list($value);
        $result = [];
        foreach ($rows as $row) {
            $item = $this->object($row);
            BuildGuard::exactKeys($item, ['source', 'target', 'kind']);
            $kind = AssetDependencyKind::tryFrom($this->string($item['kind']));
            if (null === $kind) {
                throw InvalidAssetBuildArtifactException::invalid('artifact_schema');
            }
            $result[] = new AssetDependency(ContractVersion::Version1, new LogicalAssetIdentity($this->string($item['source'])), new LogicalAssetIdentity($this->string($item['target'])), $kind);
        }

        return $result;
    }

    /** @return list<AssetEntryPoint> */
    private function entryPoints(mixed $value): array
    {
        $rows = $this->list($value);
        $result = [];
        foreach ($rows as $row) {
            $item = $this->object($row);
            BuildGuard::exactKeys($item, ['identity']);
            $result[] = new AssetEntryPoint(ContractVersion::Version1, new LogicalAssetIdentity($this->string($item['identity'])));
        }

        return $result;
    }

    /**
     * @param array<string, string> $map
     * @return list<AssetOutput>
     */
    private function outputs(mixed $value, AssetDeclarationSet $set, array $map): array
    {
        $rows = $this->list($value);
        $result = [];
        $previous = null;
        foreach ($rows as $row) {
            $item = $this->object($row);
            BuildGuard::exactKeys($item, ['identity', 'kind', 'content_type', 'content_sha256', 'byte_count', 'integrity', 'preload_hint', 'path']);
            $identity = new LogicalAssetIdentity($this->string($item['identity']));
            if (null !== $previous && strcmp($previous, $identity->value()) >= 0) {
                throw InvalidAssetBuildArtifactException::invalid('artifact_canonical');
            }
            $previous = $identity->value();
            $declaration = $set->declaration($identity);
            $kind = AssetKind::tryFrom($this->string($item['kind']));
            $type = AssetContentType::tryFrom($this->string($item['content_type']));
            $content = new ContentIdentity($this->string($item['content_sha256']));
            $count = $this->integer($item['byte_count']);
            $integrityModel = $this->object($item['integrity']);
            BuildGuard::exactKeys($integrityModel, ['algorithm', 'digest']);
            $algorithm = IntegrityAlgorithm::tryFrom($this->string($integrityModel['algorithm']));
            $preload = PreloadHint::tryFrom($this->string($item['preload_hint']));
            $path = new AssetOutputPath($this->string($item['path']));
            if (null === $declaration || null === $kind || null === $type || null === $algorithm || null === $preload || $declaration->kind() !== $kind || $declaration->contentType() !== $type || !isset($map[$path->value()])) {
                throw InvalidAssetBuildArtifactException::invalid('output_evidence_relation');
            }
            $result[] = new AssetOutput($identity, $kind, $type, $content, $count, new AssetIntegrity($algorithm, $this->string($integrityModel['digest'])), $preload, $path, $map[$path->value()]);
        }

        return $result;
    }

    /** @return array<string, mixed> */
    private function object(mixed $value): array
    {
        if (!is_array($value) || array_is_list($value)) {
            throw InvalidAssetBuildArtifactException::invalid('artifact_schema');
        }

        $copy = [];
        foreach ($value as $key => $entry) {
            if (!is_string($key)) {
                throw InvalidAssetBuildArtifactException::invalid('artifact_schema');
            }
            $copy[$key] = $entry;
        }

        return $copy;
    }

    /** @return list<mixed> */
    private function list(mixed $value): array
    {
        if (!is_array($value) || !array_is_list($value)) {
            throw InvalidAssetBuildArtifactException::invalid('artifact_schema');
        }

        return $value;
    }

    private function string(mixed $value): string
    {
        if (!is_string($value)) {
            throw InvalidAssetBuildArtifactException::invalid('artifact_schema');
        }

        return $value;
    }

    private function integer(mixed $value): int
    {
        if (!is_int($value)) {
            throw InvalidAssetBuildArtifactException::invalid('artifact_schema');
        }

        return $value;
    }
}
