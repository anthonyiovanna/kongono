<?php

declare(strict_types=1);

namespace Kongono\ViewBuild\Artifact;

use JsonException;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ViewBuild\Exception\InvalidCompiledViewArtifactException;
use Kongono\ViewBuild\Exception\InvalidViewSourceException;
use Kongono\ViewBuild\Generated\GeneratedPhpEmitter;
use Kongono\ViewBuild\Internal\BuildGuard;
use Kongono\ViewBuild\Internal\CanonicalJson;
use Kongono\ViewBuild\Source\ViewSourceSet;
use Kongono\ViewContract\Identity\CompiledViewIdentity;

/** Builds deterministic compiled metadata and inert ordinary PHP from one explicit source set. */
#[Api(ApiStatus::Experimental)]
final class CompiledViewArtifactBuilder
{
    public function build(ViewSourceSet $sourceSet): CompiledViewArtifact
    {
        $model = BuildGuard::compile($sourceSet);
        try {
            $views = BuildGuard::modelData($model);
            $modelBytes = CanonicalJson::encode(['views' => $views]);
            if (strlen($modelBytes) > BuildGuard::MAX_MODEL_BYTES) {
                throw InvalidViewSourceException::bound('model_bytes', BuildGuard::MAX_MODEL_BYTES, strlen($modelBytes));
            }
            $modelSha256 = hash('sha256', $modelBytes);
            $generatedClass = 'Kongono\\Generated\\View\\CompiledViewSet_' . $modelSha256;
            $generatedBytes = (new GeneratedPhpEmitter())->emit($model, $sourceSet->identity(), $modelSha256);
            $generatedSha256 = hash('sha256', $generatedBytes);
            $metadataBytes = CanonicalJson::encode([
                'schema' => 'kongono.compiled-view-artifact',
                'artifact_version' => 1,
                'contract_version' => 1,
                'builder' => ['package' => 'kongono/view-build', 'version' => 1],
                'source_sha256' => $sourceSet->identity()->value(),
                'model_sha256' => $modelSha256,
                'generated_php' => ['class' => $generatedClass, 'sha256' => $generatedSha256],
                'views' => $views,
            ]);
        } catch (JsonException $failure) {
            throw InvalidCompiledViewArtifactException::invalid('metadata_json', $failure);
        }
        if (strlen($metadataBytes) > CanonicalJson::MAX_BYTES) {
            throw InvalidViewSourceException::bound('artifact_bytes', CanonicalJson::MAX_BYTES, strlen($metadataBytes));
        }

        return new CompiledViewArtifact(
            $metadataBytes,
            $generatedBytes,
            new CompiledViewIdentity(hash('sha256', $metadataBytes)),
            $sourceSet->identity(),
            $modelSha256,
            $generatedSha256,
            $generatedClass,
            $model->viewCount(),
        );
    }
}
