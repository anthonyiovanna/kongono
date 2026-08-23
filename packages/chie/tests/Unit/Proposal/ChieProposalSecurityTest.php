<?php

declare(strict_types=1);

namespace Kongono\Chie\Tests\Unit\Proposal;

use Kongono\Chie\Diagnostic\ChieDiagnostic;
use Kongono\Chie\Exception\InvalidChieProposalException;
use Kongono\Chie\Exception\ProposalSpecUnavailableException;
use Kongono\Chie\Exception\ProposalTargetStaleException;
use PHPUnit\Framework\TestCase;

final class ChieProposalSecurityTest extends TestCase
{
    public function testProposalExceptionsExposeOnlyFixedPackagePresentation(): void
    {
        $secret = 'Secret Proposal Text and Private Path';
        $exceptions = [
            new InvalidChieProposalException(ChieDiagnostic::ProposalSpecInvalid, 'spec-shape'),
            new ProposalSpecUnavailableException(ChieDiagnostic::ProposalSpecUnavailable, 'spec-read'),
            new ProposalTargetStaleException(ChieDiagnostic::ProposalTargetStale, 'target-sha'),
        ];
        foreach ($exceptions as $exception) {
            self::assertStringStartsWith('KGN-CHIE-', (string) $exception);
            self::assertStringNotContainsString($secret, (string) $exception);
            self::assertStringNotContainsString('spec-read', (string) $exception);
            self::assertCount(1, $exception->diagnosticContext()->fields());
        }
    }

    public function testProposalProductionSourcesContainNoMutationOrExecutionAuthority(): void
    {
        $root = dirname(__DIR__, 5) . '/packages/chie/src';
        $paths = [
            $root . '/Proposal/ChieProposalAuthority.php',
            $root . '/Proposal/ChieProposalRequest.php',
            $root . '/Proposal/ChiePatchProposal.php',
            $root . '/Internal/ProposalSpecReader.php',
            $root . '/Internal/UnifiedDiffBuilder.php',
            $root . '/Internal/ProposalBuilder.php',
        ];
        $forbiddenCalls = ['file_put_contents(', 'unlink(', 'rename(', 'chmod(', 'proc_open(', 'shell_exec(', 'exec(', 'system(', 'passthru(', 'eval(', 'include(', 'require('];
        foreach ($paths as $path) {
            $bytes = (string) file_get_contents($path);
            foreach ($forbiddenCalls as $call) {
                self::assertStringNotContainsString($call, $bytes, $path);
            }
        }
    }

    public function testAllProposalDiagnosticsUseOneSafeViolationField(): void
    {
        $violations = [
            [ChieDiagnostic::InvalidProposalRequest, 'target-path'],
            [ChieDiagnostic::ProposalSpecUnavailable, 'spec-read'],
            [ChieDiagnostic::ProposalSpecInvalid, 'spec-shape'],
            [ChieDiagnostic::ProposalTargetUnsupported, 'target-authority'],
            [ChieDiagnostic::ProposalTargetStale, 'target-sha'],
            [ChieDiagnostic::ProposalReplacementInvalid, 'match-count'],
            [ChieDiagnostic::ProposalDiffLimitExceeded, 'diff-limit'],
            [ChieDiagnostic::ProposalConstructionFailed, 'proposal-shape'],
        ];
        foreach ($violations as [$diagnostic, $violation]) {
            $fields = $diagnostic->context($violation)->fields();
            self::assertCount(1, $fields);
            self::assertSame('violation', $fields[0]->key());
            self::assertSame($violation, $fields[0]->value());
        }
    }
}
