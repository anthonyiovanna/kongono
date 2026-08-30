<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphIntelligence\Tests\Unit\Verification;

use Kongono\ApplicationGraphArtifact\Identity\GraphIdentity;
use Kongono\ApplicationGraphIntelligence\Exception\InvalidApplicationGraphIntelligenceValueException;
use Kongono\ApplicationGraphIntelligence\Role\DeveloperIntelligenceRole;
use Kongono\ApplicationGraphIntelligence\Verification\ApplicationGraphIntelligenceVerdict;
use Kongono\ApplicationGraphIntelligence\Verification\ApplicationGraphIntelligenceVerification;
use PHPUnit\Framework\TestCase;

final class ApplicationGraphIntelligenceVerificationTest extends TestCase
{
    public function testVerdictAndNeutralSerializationAreExactAndDetached(): void
    {
        self::assertSame(['pass', 'fail'], array_column(ApplicationGraphIntelligenceVerdict::cases(), 'value'));
        $checks = array_fill_keys(ApplicationGraphIntelligenceVerification::CHECK_IDS, true);
        $verification = new ApplicationGraphIntelligenceVerification(
            ApplicationGraphIntelligenceVerdict::Pass,
            DeveloperIntelligenceRole::Hantei,
            str_repeat('a', 64),
            new GraphIdentity(str_repeat('b', 64)),
            $checks,
            [],
        );
        $returned = $verification->checkOutcomes();
        $returned['identity-match'] = false;

        self::assertSame($checks, $verification->checkOutcomes());
        self::assertSame([
            'schema' => 'kongono.application-graph-intelligence-verification',
            'version' => 1,
            'verdict' => 'pass',
            'role' => 'hantei',
            'artifact_sha256' => str_repeat('a', 64),
            'graph_identity' => 'kgn:graph:v1:graph:' . str_repeat('b', 64),
            'checks' => $checks,
            'failures' => [],
        ], $verification->toArray());
    }

    public function testInvalidVerificationFamiliesFailClosedAndNondisclosing(): void
    {
        $raw = 'TOP-SECRET-/workspace/customer.php';
        $checks = array_fill_keys(ApplicationGraphIntelligenceVerification::CHECK_IDS, true);
        $identity = new GraphIdentity(str_repeat('b', 64));
        $cases = [
            fn() => new ApplicationGraphIntelligenceVerification(ApplicationGraphIntelligenceVerdict::Pass, DeveloperIntelligenceRole::Chie, $raw, $identity, $checks, []),
            fn() => new ApplicationGraphIntelligenceVerification(ApplicationGraphIntelligenceVerdict::Pass, DeveloperIntelligenceRole::Chie, str_repeat('a', 64), $identity, array_reverse($checks, true), []),
            fn() => new ApplicationGraphIntelligenceVerification(ApplicationGraphIntelligenceVerdict::Pass, DeveloperIntelligenceRole::Chie, str_repeat('a', 64), $identity, [...$checks, 'identity-match' => $raw], []),
            fn() => new ApplicationGraphIntelligenceVerification(ApplicationGraphIntelligenceVerdict::Pass, DeveloperIntelligenceRole::Chie, str_repeat('a', 64), $identity, [...$checks, 'identity-match' => false], []),
            fn() => new ApplicationGraphIntelligenceVerification(ApplicationGraphIntelligenceVerdict::Pass, DeveloperIntelligenceRole::Chie, str_repeat('a', 64), $identity, [...$checks, 'identity-match' => false], ['identity-match-failed']),
        ];

        foreach ($cases as $case) {
            try {
                $case();
                self::fail('Invalid verification was accepted.');
            } catch (InvalidApplicationGraphIntelligenceValueException $exception) {
                self::assertSame('KGN-APPLICATION-GRAPH-INTELLIGENCE-1002', $exception->diagnosticId());
                self::assertStringNotContainsString($raw, $exception->getMessage());
                self::assertStringNotContainsString($raw, (string) $exception);
            }
        }
    }
}
