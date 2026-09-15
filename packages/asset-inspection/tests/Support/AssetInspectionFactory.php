<?php

declare(strict_types=1);

namespace Kongono\AssetInspection\Tests\Support;

use Kongono\ApplicationGraphArtifact\Identity\GraphIdentity;
use Kongono\ApplicationGraphContract\ContractVersion as GraphContractVersion;
use Kongono\ApplicationGraphIntelligence\Derivation\ApplicationGraphIntelligenceView;
use Kongono\ApplicationGraphIntelligence\Role\DeveloperIntelligenceRole as GraphRole;
use Kongono\AssetApplication\Workflow\CurrentAssetApplicationBuild;
use Kongono\AssetContract\Asset\AssetContentType;
use Kongono\AssetContract\Asset\AssetKind;
use Kongono\AssetContract\Delivery\AssetIntegrity;
use Kongono\AssetContract\Delivery\IntegrityAlgorithm;
use Kongono\AssetContract\Delivery\PreloadHint;
use Kongono\AssetContract\Dependency\AssetDependencyKind;
use Kongono\AssetContract\Identity\ContentIdentity;
use Kongono\AssetContract\Identity\LogicalAssetIdentity;
use Kongono\AssetResolution\Artifact\AssetResolutionArtifact;
use Kongono\AssetResolution\Artifact\AssetResolutionEntry;
use Kongono\AssetResolution\Artifact\AssetResolutionObject;
use Kongono\AssetResolution\ContractVersion;

final class AssetInspectionFactory
{
    public const string LOGICAL_SECRET = 'private.logical.asset';
    public const string BASENAME_SECRET = 'private-artifact-basename';
    public const string EXCEPTION_SECRET = 'private exception trace argument';

    public static function current(string $producer = 'native'): CurrentAssetApplicationBuild
    {
        $script = self::object('private script bytes', AssetKind::Script, AssetContentType::TextJavascript, PreloadHint::ModulePreload);
        $style = self::object('private style bytes', AssetKind::Style, AssetContentType::TextCss, PreloadHint::None);
        $entries = [
            new AssetResolutionEntry(
                new LogicalAssetIdentity(self::LOGICAL_SECRET),
                $script,
                [['kind' => AssetDependencyKind::Static, 'object' => $style]],
            ),
            new AssetResolutionEntry(new LogicalAssetIdentity('private.style.asset'), $style, []),
        ];
        $artifact = new AssetResolutionArtifact(
            ContractVersion::Version1,
            $producer,
            str_repeat('1', 64),
            str_repeat('2', 64),
            str_repeat('3', 64),
            $entries,
        );

        return new CurrentAssetApplicationBuild($artifact, self::BASENAME_SECRET, 2, 1, 1, 2);
    }

    public static function empty(string $producer = 'native'): CurrentAssetApplicationBuild
    {
        return new CurrentAssetApplicationBuild(
            new AssetResolutionArtifact(ContractVersion::Version1, $producer, str_repeat('4', 64), str_repeat('5', 64), str_repeat('6', 64), []),
            self::BASENAME_SECRET,
            0,
            0,
            0,
            0,
        );
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

    private static function object(string $bytes, AssetKind $kind, AssetContentType $type, PreloadHint $preload): AssetResolutionObject
    {
        $content = ContentIdentity::fromBytes($bytes);

        return new AssetResolutionObject(
            'objects/sha256/' . substr($content->value(), 0, 2) . '/' . $content->value(),
            $kind,
            $type,
            $content,
            strlen($bytes),
            new AssetIntegrity(IntegrityAlgorithm::Sha384, hash('sha384', $bytes)),
            $preload,
        );
    }

    private function __construct() {}
}
