<?php

declare(strict_types=1);

namespace Kongono\Hantei\Tests\Unit\Internal;

use Kongono\Hantei\Exception\InvalidHanteiRequestException;
use Kongono\Hantei\HanteiCheckEvidence;
use Kongono\Hantei\Internal\FrameworkQualityProfile;
use PHPUnit\Framework\TestCase;

final class FrameworkQualityProfileTest extends TestCase
{
    public function testRepositoryProfileMatchesEveryRequiredScriptExactly(): void
    {
        $root = json_decode((string) file_get_contents(dirname(__DIR__, 5) . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($root);
        self::assertIsArray($root['scripts']);
        self::assertIsArray($root['scripts']['validate:packages']);
        self::assertCount(116, $root['scripts']['validate:packages']);
        $assetAdapterContractValidation = 'composer --working-dir=packages/asset-adapter-contract validate --no-check-publish';
        self::assertSame(1, count(array_keys($root['scripts']['validate:packages'], $assetAdapterContractValidation, true)));
        $assetAdapterViteValidation = 'composer --working-dir=packages/asset-adapter-vite validate --no-check-publish';
        self::assertSame(1, count(array_keys($root['scripts']['validate:packages'], $assetAdapterViteValidation, true)));
        $assetBuildValidation = 'composer --working-dir=packages/asset-build validate --no-check-publish';
        self::assertSame(1, count(array_keys($root['scripts']['validate:packages'], $assetBuildValidation, true)));
        $assetValidation = 'composer --working-dir=packages/asset-contract validate --no-check-publish';
        self::assertSame(1, count(array_keys($root['scripts']['validate:packages'], $assetValidation, true)));
        $cacheBuildValidation = 'composer --working-dir=packages/cache-build validate --no-check-publish';
        self::assertSame(1, count(array_keys($root['scripts']['validate:packages'], $cacheBuildValidation, true)));
        $cacheAdapterContractValidation = 'composer --working-dir=packages/cache-adapter-contract validate --no-check-publish';
        self::assertSame(1, count(array_keys($root['scripts']['validate:packages'], $cacheAdapterContractValidation, true)));
        $cacheAdapterMemoryValidation = 'composer --working-dir=packages/cache-adapter-memory validate --no-check-publish';
        $cacheApplicationValidation = 'composer --working-dir=packages/cache-application validate --no-check-publish';
        $cacheInspectionValidation = 'composer --working-dir=packages/cache-inspection validate --no-check-publish';
        $cacheRuntimeValidation = 'composer --working-dir=packages/cache-runtime validate --no-check-publish';
        self::assertSame(1, count(array_keys($root['scripts']['validate:packages'], $cacheAdapterMemoryValidation, true)));
        self::assertSame(1, count(array_keys($root['scripts']['validate:packages'], $cacheApplicationValidation, true)));
        self::assertSame(1, count(array_keys($root['scripts']['validate:packages'], $cacheInspectionValidation, true)));
        $providerValidation = 'composer --working-dir=packages/model-provider-openai-responses validate --no-check-publish';
        self::assertSame(1, count(array_keys($root['scripts']['validate:packages'], $providerValidation, true)));
        $viewValidation = 'composer --working-dir=packages/view-contract validate --no-check-publish';
        self::assertSame(1, count(array_keys($root['scripts']['validate:packages'], $viewValidation, true)));
        $viewBuildValidation = 'composer --working-dir=packages/view-build validate --no-check-publish';
        self::assertSame(1, count(array_keys($root['scripts']['validate:packages'], $viewBuildValidation, true)));
        $viewHttpValidation = 'composer --working-dir=packages/view-http validate --no-check-publish';
        self::assertSame(1, count(array_keys($root['scripts']['validate:packages'], $viewHttpValidation, true)));
        $viewRuntimeValidation = 'composer --working-dir=packages/view-runtime validate --no-check-publish';
        self::assertSame(1, count(array_keys($root['scripts']['validate:packages'], $viewRuntimeValidation, true)));
        $viewApplicationValidation = 'composer --working-dir=packages/view-application validate --no-check-publish';
        self::assertSame(1, count(array_keys($root['scripts']['validate:packages'], $viewApplicationValidation, true)));
        $viewInspectionValidation = 'composer --working-dir=packages/view-inspection validate --no-check-publish';
        self::assertSame(1, count(array_keys($root['scripts']['validate:packages'], $viewInspectionValidation, true)));
        $assetInspectionValidation = 'composer --working-dir=packages/asset-inspection validate --no-check-publish';
        self::assertSame(1, count(array_keys($root['scripts']['validate:packages'], $assetInspectionValidation, true)));
        self::assertFileExists(dirname(__DIR__, 5) . '/packages/model-provider-openai-responses/src/Stream/ResponsesSseFrameReader.php');
        self::assertFileExists(dirname(__DIR__, 5) . '/packages/model-provider-openai-responses/tests/Integration/OpenAiResponsesStreamingAdapterTest.php');
        $resource = json_decode(
            (string) file_get_contents(dirname(__DIR__, 3) . '/resources/framework-quality-v1.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
        self::assertIsArray($resource);
        self::assertIsArray($resource['composer_scripts']);
        $profileScripts = $root['scripts'];
        $profileScripts['validate:packages'] = $resource['composer_scripts']['validate:packages'];
        $profileScripts['validate:skeleton'] = $resource['composer_scripts']['validate:skeleton'];
        $profileRoot = sys_get_temp_dir() . '/kongono-hantei-profile-' . bin2hex(random_bytes(8));
        self::assertTrue(mkdir($profileRoot));
        self::assertNotFalse(file_put_contents($profileRoot . '/composer.json', json_encode([
            'scripts' => $profileScripts,
        ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT)));
        try {
            $profile = FrameworkQualityProfile::load($profileRoot);
        } finally {
            self::assertTrue(unlink($profileRoot . '/composer.json'));
            self::assertTrue(rmdir($profileRoot));
        }

        self::assertSame(array_keys(HanteiCheckEvidence::CHECKS), array_column($profile->checks(), 'check_id'));
        self::assertMatchesRegularExpression('/\A[a-f0-9]{64}\z/D', $profile->sha256());
        self::assertContains('composer --working-dir=packages/agent-admission validate --no-check-publish', $root['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/agent-application validate --no-check-publish', $root['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/agent-declaration-build validate --no-check-publish', $root['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/agent-inspection validate --no-check-publish', $root['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/agent-principal-contract validate --no-check-publish', $root['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/agent-runtime validate --no-check-publish', $root['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/application-graph-artifact validate --no-check-publish', $root['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/application-graph-build validate --no-check-publish', $root['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/application-graph-composition validate --no-check-publish', $root['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/application-graph-contract validate --no-check-publish', $root['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/application-graph-intelligence validate --no-check-publish', $root['scripts']['validate:packages']);
        self::assertContains($assetAdapterContractValidation, $root['scripts']['validate:packages']);
        self::assertContains($assetAdapterViteValidation, $root['scripts']['validate:packages']);
        self::assertContains($assetValidation, $root['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/authentication validate --no-check-publish', $root['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/authorization validate --no-check-publish', $root['scripts']['validate:packages']);
        self::assertContains($cacheAdapterContractValidation, $root['scripts']['validate:packages']);
        self::assertContains($cacheAdapterMemoryValidation, $root['scripts']['validate:packages']);
        self::assertContains($cacheApplicationValidation, $root['scripts']['validate:packages']);
        self::assertContains($cacheRuntimeValidation, $root['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/composition validate --no-check-publish', $root['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/console validate --no-check-publish', $root['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/console-application validate --no-check-publish', $root['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/console-build validate --no-check-publish', $root['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/console-commands validate --no-check-publish', $root['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/console-presentation validate --no-check-publish', $root['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/console-runtime validate --no-check-publish', $root['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/database validate --no-check-publish', $root['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/database-application validate --no-check-publish', $root['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/database-mapping validate --no-check-publish', $root['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/database-migration validate --no-check-publish', $root['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/database-migration-pgsql validate --no-check-publish', $root['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/database-pdo-pgsql validate --no-check-publish', $root['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/database-sql validate --no-check-publish', $root['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/database-sql-pgsql validate --no-check-publish', $root['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/extension-admission validate --no-check-publish', $root['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/extension-contract validate --no-check-publish', $root['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/extension-build validate --no-check-publish', $root['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/extension-composition validate --no-check-publish', $root['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/extension-host validate --no-check-publish', $root['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/extension-inspection validate --no-check-publish', $root['scripts']['validate:packages']);
        self::assertSame(1, count(array_keys($root['scripts']['validate:packages'], 'composer --working-dir=packages/external-service-application validate --no-check-publish', true)));
        self::assertSame(1, count(array_keys($root['scripts']['validate:packages'], 'composer --working-dir=packages/external-service-binding-build validate --no-check-publish', true)));
        self::assertSame(1, count(array_keys($root['scripts']['validate:packages'], 'composer --working-dir=packages/external-service-contract validate --no-check-publish', true)));
        self::assertSame(1, count(array_keys($root['scripts']['validate:packages'], 'composer --working-dir=packages/external-service-credential-runtime validate --no-check-publish', true)));
        self::assertSame(1, count(array_keys($root['scripts']['validate:packages'], 'composer --working-dir=packages/external-service-inspection validate --no-check-publish', true)));
        self::assertSame(1, count(array_keys($root['scripts']['validate:packages'], 'composer --working-dir=packages/external-service-resolver-system validate --no-check-publish', true)));
        self::assertContains('composer --working-dir=packages/identity-access validate --no-check-publish', $root['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/metrics validate --no-check-publish', $root['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/model-invocation-agent-bridge validate --no-check-publish', $root['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/model-invocation-application validate --no-check-publish', $root['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/model-invocation-contract validate --no-check-publish', $root['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/model-invocation-inspection validate --no-check-publish', $root['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/model-invocation-build validate --no-check-publish', $root['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/model-invocation-runtime validate --no-check-publish', $root['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/model-provider-openai-responses-inspection validate --no-check-publish', $root['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/network-egress-application validate --no-check-publish', $root['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/network-egress-contract validate --no-check-publish', $root['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/network-egress-curl validate --no-check-publish', $root['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/network-egress-inspection validate --no-check-publish', $root['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/network-egress-policy-build validate --no-check-publish', $root['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/network-egress-runtime validate --no-check-publish', $root['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/observability validate --no-check-publish', $root['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/observability-application validate --no-check-publish', $root['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/observability-build validate --no-check-publish', $root['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/opentelemetry validate --no-check-publish', $root['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/operational-events validate --no-check-publish', $root['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/oshie validate --no-check-publish', $root['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/persistence validate --no-check-publish', $root['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/persistence-application validate --no-check-publish', $root['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/persistence-build validate --no-check-publish', $root['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/security-application validate --no-check-publish', $root['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/security-audit validate --no-check-publish', $root['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/security-enforcement validate --no-check-publish', $root['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/security-http validate --no-check-publish', $root['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/security-policy-build validate --no-check-publish', $root['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/security-runtime validate --no-check-publish', $root['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/storage-adapter-contract validate --no-check-publish', $root['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/storage-adapter-local validate --no-check-publish', $root['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/storage-application validate --no-check-publish', $root['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/storage-build validate --no-check-publish', $root['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/storage-contract validate --no-check-publish', $root['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/storage-inspection validate --no-check-publish', $root['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/storage-runtime validate --no-check-publish', $root['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/tracing validate --no-check-publish', $root['scripts']['validate:packages']);
        self::assertContains($viewValidation, $root['scripts']['validate:packages']);
        self::assertContains($viewBuildValidation, $root['scripts']['validate:packages']);
        self::assertContains($viewHttpValidation, $root['scripts']['validate:packages']);
        self::assertContains($viewInspectionValidation, $root['scripts']['validate:packages']);
        self::assertContains($viewRuntimeValidation, $root['scripts']['validate:packages']);
        self::assertSame([
            'composer --working-dir=skeletons/asset-minimal validate --no-check-publish',
            'composer --working-dir=skeletons/cache-minimal validate --no-check-publish',
            'composer --working-dir=skeletons/data-minimal validate --no-check-publish',
            'composer --working-dir=skeletons/minimal validate --no-check-publish',
            'composer --working-dir=skeletons/observability-minimal validate --no-check-publish',
            'composer --working-dir=skeletons/persistence-minimal validate --no-check-publish',
            'composer --working-dir=skeletons/security-minimal validate --no-check-publish',
            'composer --working-dir=skeletons/storage-minimal validate --no-check-publish',
            'composer --working-dir=skeletons/view-minimal validate --no-check-publish',
        ], $root['scripts']['validate:skeleton']);
        self::assertIsArray($root['scripts']['quality']);
        self::assertSame('@validate:skeleton', $root['scripts']['quality'][2]);
        self::assertSame('@php tests/External/Persistence/run.php', $root['scripts']['acceptance:phase7']);
        self::assertSame('@php tests/External/Console/run.php', $root['scripts']['acceptance:phase8']);
        self::assertSame('@php tests/External/Security/run.php', $root['scripts']['acceptance:phase9']);
        self::assertSame('@php tests/External/Observability/run.php', $root['scripts']['acceptance:phase10']);
        self::assertSame('@php tests/External/ApplicationGraph/run.php', $root['scripts']['acceptance:phase11']);
        self::assertSame('@php tests/External/ExtensionPlatform/run.php', $root['scripts']['acceptance:phase12']);
        self::assertSame('@php tests/External/AgentPrincipal/run.php', $root['scripts']['acceptance:phase13']);
        self::assertSame('@php tests/External/ModelInvocation/run.php', $root['scripts']['acceptance:phase14']);
        self::assertSame('@php tests/External/NetworkEgress/run.php', $root['scripts']['acceptance:phase15']);
        self::assertSame('@php tests/External/ExternalService/run.php', $root['scripts']['acceptance:phase16']);
        self::assertSame('@php tests/External/OpenAiResponses/run.php', $root['scripts']['acceptance:phase17']);
        self::assertSame('@php tests/External/View/run.php', $root['scripts']['acceptance:phase18']);
        self::assertSame('@php tests/External/Asset/run.php', $root['scripts']['acceptance:phase19']);
        self::assertSame('@php tests/External/Cache/run.php', $root['scripts']['acceptance:phase20']);
        self::assertSame('@php tests/External/Storage/run.php', $root['scripts']['acceptance:phase21']);

        $resourceValidation = $resource['composer_scripts']['validate:packages'] ?? null;
        self::assertIsArray($resourceValidation);
        self::assertCount(116, $resourceValidation);
        self::assertSame($root['scripts']['validate:packages'], $resourceValidation);
        self::assertSame($root['scripts']['validate:skeleton'], $resource['composer_scripts']['validate:skeleton']);
        self::assertSame($root['scripts']['acceptance:phase7'], $resource['composer_scripts']['acceptance:phase7']);
        self::assertSame($root['scripts']['acceptance:phase8'], $resource['composer_scripts']['acceptance:phase8']);
        self::assertSame($root['scripts']['acceptance:phase9'], $resource['composer_scripts']['acceptance:phase9']);
        self::assertSame($root['scripts']['acceptance:phase10'], $resource['composer_scripts']['acceptance:phase10']);
        self::assertSame($root['scripts']['acceptance:phase11'], $resource['composer_scripts']['acceptance:phase11']);
        self::assertSame($root['scripts']['acceptance:phase12'], $resource['composer_scripts']['acceptance:phase12']);
        self::assertSame($root['scripts']['acceptance:phase13'], $resource['composer_scripts']['acceptance:phase13']);
        self::assertSame($root['scripts']['acceptance:phase14'], $resource['composer_scripts']['acceptance:phase14']);
        self::assertSame($root['scripts']['acceptance:phase15'], $resource['composer_scripts']['acceptance:phase15']);
        self::assertSame($root['scripts']['acceptance:phase16'], $resource['composer_scripts']['acceptance:phase16']);
        self::assertSame($root['scripts']['acceptance:phase17'], $resource['composer_scripts']['acceptance:phase17']);
        self::assertSame($root['scripts']['acceptance:phase18'], $resource['composer_scripts']['acceptance:phase18']);
        self::assertSame($root['scripts']['acceptance:phase19'], $resource['composer_scripts']['acceptance:phase19']);
        self::assertSame($root['scripts']['acceptance:phase20'], $resource['composer_scripts']['acceptance:phase20']);
        self::assertSame($root['scripts']['acceptance:phase21'], $resource['composer_scripts']['acceptance:phase21']);
    }

    public function testUnknownProfileIsRejected(): void
    {
        $this->expectException(InvalidHanteiRequestException::class);
        FrameworkQualityProfile::load(dirname(__DIR__, 5), 'caller-profile');
    }
}
