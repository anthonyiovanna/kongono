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
        $root = dirname(__DIR__, 5);
        $profile = FrameworkQualityProfile::load($root);

        self::assertSame(array_keys(HanteiCheckEvidence::CHECKS), array_column($profile->checks(), 'check_id'));
        self::assertMatchesRegularExpression('/\A[a-f0-9]{64}\z/D', $profile->sha256());
        $root = json_decode((string) file_get_contents(dirname(__DIR__, 5) . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($root);
        self::assertIsArray($root['scripts']);
        self::assertIsArray($root['scripts']['validate:packages']);
        self::assertCount(87, $root['scripts']['validate:packages']);
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
        self::assertContains('composer --working-dir=packages/authentication validate --no-check-publish', $root['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/authorization validate --no-check-publish', $root['scripts']['validate:packages']);
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
        self::assertContains('composer --working-dir=packages/tracing validate --no-check-publish', $root['scripts']['validate:packages']);
        self::assertSame([
            'composer --working-dir=skeletons/data-minimal validate --no-check-publish',
            'composer --working-dir=skeletons/minimal validate --no-check-publish',
            'composer --working-dir=skeletons/observability-minimal validate --no-check-publish',
            'composer --working-dir=skeletons/persistence-minimal validate --no-check-publish',
            'composer --working-dir=skeletons/security-minimal validate --no-check-publish',
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

        $resource = json_decode(
            (string) file_get_contents(dirname(__DIR__, 3) . '/resources/framework-quality-v1.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
        self::assertIsArray($resource);
        self::assertIsArray($resource['composer_scripts']);
        self::assertSame($root['scripts']['validate:packages'], $resource['composer_scripts']['validate:packages']);
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
    }

    public function testUnknownProfileIsRejected(): void
    {
        $this->expectException(InvalidHanteiRequestException::class);
        FrameworkQualityProfile::load(dirname(__DIR__, 5), 'caller-profile');
    }
}
