<?php

declare(strict_types=1);

namespace Kongono\ViewInspection\Tests\Support;

use Kongono\ApplicationGraphArtifact\Identity\GraphIdentity;
use Kongono\ApplicationGraphContract\ContractVersion as GraphContractVersion;
use Kongono\ApplicationGraphIntelligence\Derivation\ApplicationGraphIntelligenceView;
use Kongono\ApplicationGraphIntelligence\Role\DeveloperIntelligenceRole as GraphRole;
use Kongono\ViewApplication\Definition\ViewApplicationDefinition;
use Kongono\ViewApplication\Profile\ViewApplicationProfile;
use Kongono\ViewApplication\Profile\ViewApplicationProfileIdentity;
use Kongono\ViewApplication\Workflow\CurrentViewApplicationBuild;
use Kongono\ViewBuild\Artifact\CompiledViewArtifact;
use Kongono\ViewBuild\Artifact\CompiledViewArtifactBuilder;
use Kongono\ViewBuild\Source\ViewSourceIdentity;
use Kongono\ViewBuild\Source\ViewSourceParser;
use Kongono\ViewContract\Identity\CompiledViewIdentity;

final class ViewInspectionFactory
{
    public const string SOURCE_SECRET = 'hostile-template-secret.example/private';
    public const string PATH_SECRET = 'C:/private/view-source-secret/home.view.json';
    public const string CONTEXT_SECRET = 'private_context_value';
    public const string RENDERED_SECRET = '<h1>private rendered bytes</h1>';
    public const string HEADER_SECRET = 'Set-Cookie: private=true';
    public const string EXCEPTION_SECRET = 'private exception trace argument';

    public static function profile(string $text = self::SOURCE_SECRET, bool $secondView = false): ViewApplicationProfile
    {
        $views = [[
            'id' => 'page.home',
            'context' => ['kind' => 'record', 'fields' => [[
                'name' => 'message',
                'required' => true,
                'schema' => ['kind' => 'string', 'max_bytes' => 64],
            ]]],
            'body' => [
                ['kind' => 'text', 'text' => $text],
                ['kind' => 'value', 'path' => '$.message'],
            ],
        ]];
        if ($secondView) {
            $views[] = [
                'id' => 'page.second',
                'context' => ['kind' => 'record', 'fields' => []],
                'body' => [['kind' => 'text', 'text' => 'second-private-template']],
            ];
        }
        $bytes = json_encode([
            'schema' => 'kongono.view-source-set',
            'version' => 1,
            'views' => $views,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
        $source = (new ViewSourceParser())->parse($bytes);
        $artifact = (new CompiledViewArtifactBuilder())->build($source);

        return self::profileFromArtifact($artifact);
    }

    public static function profileFromArtifact(CompiledViewArtifact $artifact, ?ViewApplicationProfileIdentity $identity = null): ViewApplicationProfile
    {
        $current = new CurrentViewApplicationBuild(
            new ViewApplicationDefinition('C:/private/application-root', self::PATH_SECRET, 'C:/private/artifact-output'),
            $artifact,
        );
        $identity ??= new ViewApplicationProfileIdentity(hash('sha256', implode("\n", [
            'kongono.view-application-profile',
            '1',
            $artifact->sourceIdentity()->value(),
            $artifact->compiledViewIdentity()->value(),
            $artifact->modelSha256(),
            $artifact->generatedPhpSha256(),
            (string) $artifact->viewCount(),
        ])));

        return new ViewApplicationProfile($current, $identity);
    }

    public static function structurallyCorruptProfile(): ViewApplicationProfile
    {
        $artifact = self::profile()->currentBuild()->artifact();
        $metadata = $artifact->metadataBytes() . self::CONTEXT_SECRET;

        return self::profileFromArtifact(new CompiledViewArtifact(
            $metadata,
            $artifact->generatedPhpBytes(),
            new CompiledViewIdentity(hash('sha256', $metadata)),
            $artifact->sourceIdentity(),
            $artifact->modelSha256(),
            $artifact->generatedPhpSha256(),
            $artifact->generatedClass(),
            $artifact->viewCount(),
        ));
    }

    public static function reconstructedMismatchProfile(): ViewApplicationProfile
    {
        $artifact = self::profile()->currentBuild()->artifact();
        $mismatch = new CompiledViewArtifact(
            $artifact->metadataBytes(),
            $artifact->generatedPhpBytes(),
            $artifact->compiledViewIdentity(),
            new ViewSourceIdentity(str_repeat('f', 64)),
            $artifact->modelSha256(),
            $artifact->generatedPhpSha256(),
            $artifact->generatedClass(),
            $artifact->viewCount(),
        );

        return self::profileFromArtifact($mismatch);
    }

    public static function graphView(GraphRole $role = GraphRole::Chie, int $serviceCount = 4): ApplicationGraphIntelligenceView
    {
        return new ApplicationGraphIntelligenceView(
            $role,
            str_repeat('a', 64),
            new GraphIdentity(str_repeat('b', 64)),
            GraphContractVersion::Version1,
            6 + $serviceCount,
            6,
            ['composition.input' => 1, 'composition.service' => $serviceCount, 'composition.contract' => 3, 'composition.root' => 2],
            ['composition.depends_on' => 2, 'composition.consumes_input' => 1, 'composition.implements_contract' => 2, 'composition.exposes_root' => 1],
        );
    }

    private function __construct() {}
}
