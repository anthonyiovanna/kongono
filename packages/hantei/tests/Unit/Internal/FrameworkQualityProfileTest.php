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
        self::assertCount(57, $root['scripts']['validate:packages']);
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
        self::assertContains('composer --working-dir=packages/identity-access validate --no-check-publish', $root['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/metrics validate --no-check-publish', $root['scripts']['validate:packages']);
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
    }

    public function testUnknownProfileIsRejected(): void
    {
        $this->expectException(InvalidHanteiRequestException::class);
        FrameworkQualityProfile::load(dirname(__DIR__, 5), 'caller-profile');
    }
}
