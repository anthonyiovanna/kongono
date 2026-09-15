<?php

declare(strict_types=1);

namespace AssetMinimalApp;

use Kongono\AssetApplication\Definition\AssetApplicationDefinition;
use Kongono\AssetApplication\Summary\AssetApplicationSummary;
use Kongono\AssetApplication\Summary\ResolvedAssetSummary;
use Kongono\AssetApplication\Workflow\AssetApplicationWorkflow;
use Kongono\AssetContract\Identity\LogicalAssetIdentity;
use Kongono\AssetResolution\Browser\PublicAssetBaseUrl;
use Kongono\AssetResolution\Policy\AssetCachePolicy;
use Kongono\AssetResolution\Policy\AssetIntegrityPolicy;
use Kongono\AssetResolution\Policy\AssetPreloadPolicy;
use Kongono\AssetResolution\Policy\AssetResolutionPolicy;

/** Owns this copied application's explicit asset inputs and browser policy. */
final readonly class AssetWorkflow
{
    private AssetApplicationDefinition $definition;

    public function __construct(private string $root)
    {
        $this->definition = new AssetApplicationDefinition($root, $root . '/kongono.assets.json', $root . '/var/assets');
    }

    public function plan(): AssetApplicationSummary
    {
        return AssetApplicationSummary::fromPlan((new AssetApplicationWorkflow())->plan($this->definition), 'plan');
    }

    public function build(): AssetApplicationSummary
    {
        return AssetApplicationSummary::fromCurrent((new AssetApplicationWorkflow())->build($this->definition), 'apply');
    }

    public function check(): AssetApplicationSummary
    {
        return AssetApplicationSummary::fromCurrent((new AssetApplicationWorkflow())->check($this->definition));
    }

    public function resolve(): ResolvedAssetSummary
    {
        $policy = new AssetResolutionPolicy(
            new PublicAssetBaseUrl('/assets/'),
            AssetCachePolicy::PublicImmutable,
            AssetIntegrityPolicy::RequireArtifactSha384,
            AssetPreloadPolicy::HonorArtifact,
        );

        return ResolvedAssetSummary::fromResolved((new AssetApplicationWorkflow())->resolve($this->definition, new LogicalAssetIdentity('app.style'), $policy));
    }

    /** @return array<string, bool|int|string> */
    public static function assetRecord(AssetApplicationSummary $summary): array
    {
        return [
            'schema' => $summary->schema(),
            'version' => $summary->version(),
            'producer_kind' => $summary->producerKind(),
            'source_set_identity' => $summary->sourceSetIdentity(),
            'graph_identity' => $summary->graphIdentity(),
            'build_identity' => $summary->buildIdentity(),
            'resolution_identity' => $summary->resolutionIdentity(),
            'artifact_basename' => $summary->artifactBasename(),
            'declaration_count' => $summary->declarationCount(),
            'dependency_count' => $summary->dependencyCount(),
            'entry_point_count' => $summary->entryPointCount(),
            'output_count' => $summary->outputCount(),
            'current' => $summary->current(),
            'publication_required' => $summary->publicationRequired(),
        ];
    }

    /** @return array<string, int|string> */
    public static function resolvedRecord(ResolvedAssetSummary $summary): array
    {
        return [
            'schema' => $summary->schema(),
            'version' => $summary->version(),
            'logical_identity' => $summary->logicalIdentity(),
            'public_url' => $summary->publicUrl(),
            'content_identity' => $summary->contentIdentity(),
            'kind' => $summary->kind(),
            'content_type' => $summary->contentType(),
            'byte_count' => $summary->byteCount(),
            'cache_control' => $summary->cacheControl(),
            'etag' => $summary->etag(),
            'integrity' => $summary->integrity() ?? '',
            'preload' => $summary->preload(),
            'dependency_count' => $summary->dependencyCount(),
        ];
    }
}
