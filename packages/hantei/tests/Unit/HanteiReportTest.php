<?php

declare(strict_types=1);

namespace Kongono\Hantei\Tests\Unit;

use Kongono\Hantei\Exception\InvalidHanteiValueException;
use Kongono\Hantei\HanteiCheckEvidence;
use Kongono\Hantei\HanteiCheckOutcome;
use Kongono\Hantei\HanteiReport;
use Kongono\Hantei\HanteiVerdict;
use PHPUnit\Framework\TestCase;

final class HanteiReportTest extends TestCase
{
    public function testPassReportHasExactShapeAndDefensiveEvidence(): void
    {
        $checks = self::passChecks();
        $report = new HanteiReport(HanteiVerdict::Pass, str_repeat('a', 64), str_repeat('b', 64), str_repeat('c', 64), str_repeat('d', 64), '8.5.0', str_repeat('e', 64), 'git version 2.51.0', $checks, []);
        $checks[0] = self::blocked('composer-schema', 'validate:composer', 'invalid-evidence');

        self::assertSame(['schema', 'version', 'role', 'verdict', 'profile', 'profile_sha256', 'subject_sha256', 'framework_artifact_sha256', 'source_set_sha256', 'php_version', 'composer_binary_sha256', 'git_version', 'checks', 'uncertainties'], array_keys($report->toArray()));
        self::assertSame('PASS', $report->toArray()['verdict']);
        self::assertSame('PASS', $report->checks()[0]->outcome()->value);
        self::assertSame([], $report->toArray()['uncertainties']);
    }

    public function testFailPrecedesBlockedAndBlockedReasonsRemainVisible(): void
    {
        $checks = self::passChecks();
        $checks[0] = new HanteiCheckEvidence('composer-schema', 'validate:composer', HanteiCheckOutcome::Fail, 1, 0, hash('sha256', ''), 0, hash('sha256', ''), null);
        $checks[1] = self::blocked('package-schemas', 'validate:packages', 'tool-unavailable');
        $report = new HanteiReport(HanteiVerdict::Fail, str_repeat('a', 64), str_repeat('b', 64), str_repeat('c', 64), str_repeat('d', 64), '8.5.0', str_repeat('e', 64), 'git version 2.51.0', $checks, ['tool-unavailable']);

        self::assertSame(HanteiVerdict::Fail, $report->verdict());
        self::assertSame(['tool-unavailable'], $report->toArray()['uncertainties']);
    }

    public function testInvalidOrReorderedEvidenceCannotConstructReport(): void
    {
        $checks = self::passChecks();
        [$checks[0], $checks[1]] = [$checks[1], $checks[0]];

        $this->expectException(InvalidHanteiValueException::class);
        new HanteiReport(HanteiVerdict::Pass, str_repeat('a', 64), str_repeat('b', 64), str_repeat('c', 64), str_repeat('d', 64), '8.5.0', str_repeat('e', 64), 'git version 2.51.0', $checks, []);
    }

    public function testEvidenceRejectsUnknownPairAndInconsistentOutcome(): void
    {
        $this->expectException(InvalidHanteiValueException::class);
        new HanteiCheckEvidence('unknown', 'test', HanteiCheckOutcome::Pass, 0, 0, hash('sha256', ''), 0, hash('sha256', ''), null);
    }

    /** @return list<HanteiCheckEvidence> */
    private static function passChecks(): array
    {
        $checks = [];
        foreach (HanteiCheckEvidence::CHECKS as $checkId => $script) {
            $checks[] = new HanteiCheckEvidence($checkId, $script, HanteiCheckOutcome::Pass, 0, 0, hash('sha256', ''), 0, hash('sha256', ''), null);
        }

        return $checks;
    }

    private static function blocked(string $checkId, string $script, string $reason): HanteiCheckEvidence
    {
        return new HanteiCheckEvidence($checkId, $script, HanteiCheckOutcome::Blocked, null, 0, null, 0, null, $reason);
    }
}
