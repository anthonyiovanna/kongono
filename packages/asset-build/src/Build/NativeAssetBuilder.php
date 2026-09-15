<?php

declare(strict_types=1);

namespace Kongono\AssetBuild\Build;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetBuild\Artifact\AssetBuildArtifactValidator;
use Kongono\AssetBuild\Graph\AssetGraphBuilder;
use Kongono\AssetBuild\Internal\BuildGuard;
use Kongono\AssetBuild\Internal\CanonicalJson;
use Kongono\AssetBuild\Source\AssetSourceSet;
use Kongono\AssetContract\Delivery\AssetIntegrity;
use Kongono\AssetContract\Delivery\IntegrityAlgorithm;
use Kongono\AssetContract\Delivery\PreloadHint;

/** Performs the fixed synchronous stateless byte-identical native build. */
#[Api(ApiStatus::Experimental)]
final class NativeAssetBuilder
{
    public function build(AssetSourceSet $sourceSet): AssetBuildResult
    {
        $graph = (new AssetGraphBuilder())->build($sourceSet);
        $outputs = [];
        $objects = [];
        $outputModel = [];
        foreach ($sourceSet->sources() as $source) {
            $declaration = $sourceSet->declarationSet()->declaration($source->identity());
            if (null === $declaration) {
                throw new \LogicException('Complete source-set relation was lost.');
            }
            $path = AssetOutputPath::fromContentIdentity($source->contentIdentity());
            $integrity = new AssetIntegrity(IntegrityAlgorithm::Sha384, hash('sha384', $source->bytes()));
            $output = new AssetOutput($source->identity(), $declaration->kind(), $declaration->contentType(), $source->contentIdentity(), $source->byteCount(), $integrity, PreloadHint::None, $path, $source->bytes());
            $outputs[] = $output;
            $objects[$path->value()] = $source->bytes();
            $outputModel[] = [
                'identity' => $source->identity()->value(),
                'kind' => $declaration->kind()->value,
                'content_type' => $declaration->contentType()->value,
                'content_sha256' => $source->contentIdentity()->value(),
                'byte_count' => $source->byteCount(),
                'integrity' => ['algorithm' => IntegrityAlgorithm::Sha384->value, 'digest' => $integrity->digest()],
                'preload_hint' => PreloadHint::None->value,
                'path' => $path->value(),
            ];
        }
        ksort($objects, SORT_STRING);
        $set = $sourceSet->declarationSet();
        $artifactBytes = CanonicalJson::encode([
            'schema' => 'kongono.asset-build-artifact',
            'artifact_version' => 1,
            'contract_version' => $set->contractVersion()->value,
            'builder' => ['package' => 'kongono/asset-build', 'profile' => NativeBuildProfile::NativeCopyV1->value, 'version' => 1],
            'source_set_sha256' => $sourceSet->identity()->value(),
            'graph_sha256' => $graph->identity()->value(),
            'declarations' => BuildGuard::declarations($set),
            'dependencies' => BuildGuard::dependencies($set),
            'entry_points' => BuildGuard::entryPoints($set),
            'outputs' => $outputModel,
        ]);
        $artifact = (new AssetBuildArtifactValidator())->validateStructure($artifactBytes, $objects);

        return new AssetBuildResult($artifact, $outputs, $objects);
    }
}
