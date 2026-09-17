<?php

declare(strict_types=1);

namespace Kongono\CacheInspection\Tests\Support;

use Kongono\ApplicationGraphArtifact\Identity\GraphIdentity;
use Kongono\ApplicationGraphContract\ContractVersion as GraphContractVersion;
use Kongono\ApplicationGraphIntelligence\Derivation\ApplicationGraphIntelligenceView;
use Kongono\ApplicationGraphIntelligence\Role\DeveloperIntelligenceRole as GraphRole;
use Kongono\CacheApplication\Workflow\CurrentCacheApplicationBuild;
use Kongono\CacheBuild\Build\CacheProfileBuilder;
use Kongono\CacheBuild\Profile\CacheProfileDeclaration;
use Kongono\CacheBuild\Profile\CacheProfileName;
use Kongono\CacheBuild\Profile\CacheProfileSet;
use Kongono\CacheBuild\Profile\CacheProfileValueLimit;
use Kongono\CacheContract\Codec\CacheCodecIdentity;
use Kongono\CacheContract\ContractVersion;
use Kongono\CacheContract\Freshness\CacheLifetime;
use Kongono\CacheContract\Identity\CacheNamespace;
use Kongono\CacheInspection\RuntimeEvidence\DetachedCacheRuntimeEvidence;

final class CacheInspectionFactory
{
    public const string PROFILE_SECRET = 'private-profile';
    public const string NAMESPACE_SECRET = 'private-namespace';
    public const string CODEC_SECRET = 'private-codec';
    public const string KEY_SECRET = 'private-key';
    public const string VALUE_SECRET = 'private-value-bytes';
    public const string PROVIDER_SECRET = 'private-provider';
    public const string EXCEPTION_SECRET = 'private exception trace argument';

    public static function current(bool $empty = false, string $suffix = ''): CurrentCacheApplicationBuild
    {
        $profiles = [];
        if (!$empty) {
            $profiles = [
                new CacheProfileDeclaration(
                    ContractVersion::Version1,
                    new CacheProfileName(self::PROFILE_SECRET . $suffix),
                    new CacheNamespace(self::NAMESPACE_SECRET . $suffix),
                    new CacheCodecIdentity(self::CODEC_SECRET . $suffix, 1),
                    new CacheLifetime(1_000_000),
                    new CacheProfileValueLimit(4096),
                ),
                new CacheProfileDeclaration(
                    ContractVersion::Version1,
                    new CacheProfileName('secondary-profile' . $suffix),
                    new CacheNamespace('secondary-namespace' . $suffix),
                    new CacheCodecIdentity('secondary-codec' . $suffix, 2),
                    new CacheLifetime(2_000_000),
                    new CacheProfileValueLimit(8192),
                ),
            ];
        }
        $set = new CacheProfileSet($profiles);
        $artifact = (new CacheProfileBuilder())->build($set)->artifact();

        return new CurrentCacheApplicationBuild($set, $artifact);
    }

    public static function runtime(CurrentCacheApplicationBuild $current, int $offset = 0): DetachedCacheRuntimeEvidence
    {
        $counts = [];
        foreach (DetachedCacheRuntimeEvidence::OUTCOMES as $position => $outcome) {
            $counts[$outcome] = $position + $offset;
        }

        return new DetachedCacheRuntimeEvidence([
            'profile_set_sha256' => $current->profiles()->identity()->value(),
            'build_sha256' => $current->artifact()->buildIdentity()->value(),
            'artifact_sha256' => $current->artifact()->artifactIdentity()->value(),
        ], $counts);
    }

    public static function zeroRuntime(CurrentCacheApplicationBuild $current): DetachedCacheRuntimeEvidence
    {
        return new DetachedCacheRuntimeEvidence([
            'profile_set_sha256' => $current->profiles()->identity()->value(),
            'build_sha256' => $current->artifact()->buildIdentity()->value(),
            'artifact_sha256' => $current->artifact()->artifactIdentity()->value(),
        ], array_fill_keys(DetachedCacheRuntimeEvidence::OUTCOMES, 0));
    }

    public static function graph(GraphRole $role = GraphRole::Chie, int $services = 4): ApplicationGraphIntelligenceView
    {
        return new ApplicationGraphIntelligenceView(
            $role,
            str_repeat('a', 64),
            new GraphIdentity(str_repeat('b', 64)),
            GraphContractVersion::Version1,
            6 + $services,
            6,
            ['composition.input' => 1, 'composition.service' => $services, 'composition.contract' => 3, 'composition.root' => 2],
            ['composition.depends_on' => 2, 'composition.consumes_input' => 1, 'composition.implements_contract' => 2, 'composition.exposes_root' => 1],
        );
    }

    private function __construct() {}
}
