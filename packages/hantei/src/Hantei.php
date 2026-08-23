<?php

declare(strict_types=1);

namespace Kongono\Hantei;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Hantei\Exception\InvalidHanteiRequestException;
use Kongono\Hantei\Internal\FrameworkQualityProfile;
use Kongono\Hantei\Internal\FrameworkSubject;
use Kongono\Hantei\Internal\ProcessResult;
use Kongono\Hantei\Internal\ProcessRunner;
use Kongono\Hantei\Internal\VerdictEvaluator;
use Kongono\Knowledge\Artifact\FrameworkKnowledgeArtifactGenerator;
use Kongono\Knowledge\Artifact\FrameworkKnowledgeArtifactValidator;
use Throwable;

/** Runs all eight fixed checks against one exact unchanged framework subject. */
#[Api(ApiStatus::Experimental)]
final class Hantei
{
    public function verify(string $frameworkRoot): HanteiReport
    {
        $runner = new ProcessRunner();
        try {
            $root = realpath($frameworkRoot);
            if (false === $root || !is_dir($root) || is_link($root)) {
                return $this->blockedReport('subject-unavailable');
            }
            $profile = FrameworkQualityProfile::load($root);
        } catch (InvalidHanteiRequestException) {
            return $this->blockedReport('profile-mismatch');
        }

        try {
            $initialSubject = FrameworkSubject::capture($root, $runner);
        } catch (Throwable) {
            return $this->blockedReport('subject-unavailable', $profile->sha256());
        }

        try {
            $artifact = (new FrameworkKnowledgeArtifactGenerator())->generate($root);
            $artifact = (new FrameworkKnowledgeArtifactValidator())->validateCurrent($root, $artifact->canonicalBytes());
        } catch (Throwable) {
            return $this->blockedReport('knowledge-unavailable', $profile->sha256(), $initialSubject->sha256());
        }

        $composer = $this->composerBinary();
        if (null === $composer) {
            return $this->blockedReport(
                'tool-unavailable',
                $profile->sha256(),
                $initialSubject->sha256(),
                $artifact->artifactSha256(),
                $artifact->sourceSetSha256(),
            );
        }
        [$composerPath, $composerSha256] = $composer;
        $gitVersion = $this->gitVersion($root, $runner);
        if (null === $gitVersion) {
            return $this->blockedReport(
                'tool-unavailable',
                $profile->sha256(),
                $initialSubject->sha256(),
                $artifact->artifactSha256(),
                $artifact->sourceSetSha256(),
                $composerSha256,
            );
        }

        try {
            $preChecks = FrameworkSubject::capture($root, $runner);
        } catch (Throwable) {
            return $this->blockedReport(
                'subject-unavailable',
                $profile->sha256(),
                $initialSubject->sha256(),
                $artifact->artifactSha256(),
                $artifact->sourceSetSha256(),
                $composerSha256,
                $gitVersion,
            );
        }
        if ($preChecks->sha256() !== $initialSubject->sha256()) {
            return $this->blockedReport(
                'subject-changed',
                $profile->sha256(),
                $initialSubject->sha256(),
                $artifact->artifactSha256(),
                $artifact->sourceSetSha256(),
                $composerSha256,
                $gitVersion,
            );
        }

        $checks = [];
        foreach ($profile->checks() as $index => $definition) {
            $result = $runner->run([
                PHP_BINARY,
                $composerPath,
                '--working-dir=' . $root,
                '--no-interaction',
                'run-script',
                $definition['composer_script'],
            ], $root, 600, HanteiCheckEvidence::MAX_STREAM_BYTES);
            $checks[] = $this->evidence($definition['check_id'], $definition['composer_script'], $result);

            try {
                $current = FrameworkSubject::capture($root, $runner);
            } catch (Throwable) {
                $checks = $this->replaceAndFillBlocked($checks, $profile->checks(), $index, 'subject-unavailable');
                break;
            }
            if ($current->sha256() !== $initialSubject->sha256()) {
                $checks = $this->replaceAndFillBlocked($checks, $profile->checks(), $index, 'subject-changed');
                break;
            }
        }

        if (count($checks) === count(HanteiCheckEvidence::CHECKS)) {
            try {
                $complete = FrameworkSubject::capture($root, $runner);
                if ($complete->sha256() !== $initialSubject->sha256()) {
                    $checks = $this->replaceAndFillBlocked($checks, $profile->checks(), count($checks) - 1, 'subject-changed');
                }
            } catch (Throwable) {
                $checks = $this->replaceAndFillBlocked($checks, $profile->checks(), count($checks) - 1, 'subject-unavailable');
            }
        }

        $uncertainties = $this->uncertainties($checks);

        return new HanteiReport(
            (new VerdictEvaluator())->evaluate($checks),
            $profile->sha256(),
            $initialSubject->sha256(),
            $artifact->artifactSha256(),
            $artifact->sourceSetSha256(),
            PHP_VERSION,
            $composerSha256,
            $gitVersion,
            $checks,
            $uncertainties,
        );
    }

    /** @return array{string, string}|null */
    private function composerBinary(): ?array
    {
        $candidate = getenv('COMPOSER_BINARY');
        if (!is_string($candidate) || '' === $candidate || !$this->absolutePath($candidate)) {
            return null;
        }
        $resolved = realpath($candidate);
        $stat = @lstat($candidate);
        if (false === $resolved || false === $stat || is_link($candidate) || !is_file($candidate) || !is_readable($candidate) || (($stat['mode'] & 0170000) !== 0100000)) {
            return null;
        }
        $bytes = file_get_contents($resolved);
        if (false === $bytes) {
            return null;
        }

        return [$resolved, hash('sha256', $bytes)];
    }

    private function gitVersion(string $root, ProcessRunner $runner): ?string
    {
        $result = $runner->run(['git', '--version'], $root, 30, 4096);
        if (!$result->started() || 0 !== $result->exitCode() || null !== $result->blockedReason() || '' !== $result->stderr()) {
            return null;
        }
        $version = trim($result->stdout());

        return 1 === preg_match('/\Agit version [ -~]{1,128}\z/D', $version) ? $version : null;
    }

    private function evidence(string $checkId, string $script, ProcessResult $result): HanteiCheckEvidence
    {
        if (!$result->started()) {
            return $this->blockedEvidence($checkId, $script, 'process-start-failed');
        }
        $exitCode = $result->exitCode() ?? 1;
        $stdout = $result->stdout();
        $stderr = $result->stderr();
        $reason = $result->blockedReason();
        if (null === $reason && 0 !== $exitCode && 'locked-advisories' === $checkId && $this->auditUnavailable($stdout . "\n" . $stderr)) {
            $reason = 'audit-unavailable';
        }
        $outcome = null !== $reason
            ? HanteiCheckOutcome::Blocked
            : (0 === $exitCode ? HanteiCheckOutcome::Pass : HanteiCheckOutcome::Fail);

        return new HanteiCheckEvidence(
            $checkId,
            $script,
            $outcome,
            $exitCode,
            strlen($stdout),
            hash('sha256', $stdout),
            strlen($stderr),
            hash('sha256', $stderr),
            $reason,
        );
    }

    private function auditUnavailable(string $output): bool
    {
        if (1 === preg_match('/(?:vulnerabilit|malware|abandoned package|policy failure|schema failure|dependency failure)/i', $output)) {
            return false;
        }

        return 1 === preg_match('/(?:curl error [0-9]+|could not resolve host|failed to connect|network is unreachable|connection timed out|operation timed out|could not connect to server)/i', $output);
    }

    /**
     * @param list<HanteiCheckEvidence>                              $checks
     * @param list<array{check_id: string, composer_script: string}> $definitions
     *
     * @return list<HanteiCheckEvidence>
     */
    private function replaceAndFillBlocked(array $checks, array $definitions, int $index, string $reason): array
    {
        $checks[$index] = $this->blockedEvidence($definitions[$index]['check_id'], $definitions[$index]['composer_script'], $reason);
        for ($next = $index + 1; $next < count($definitions); ++$next) {
            $checks[] = $this->blockedEvidence($definitions[$next]['check_id'], $definitions[$next]['composer_script'], $reason);
        }

        return array_values($checks);
    }

    private function blockedEvidence(string $checkId, string $script, string $reason): HanteiCheckEvidence
    {
        return new HanteiCheckEvidence($checkId, $script, HanteiCheckOutcome::Blocked, null, 0, null, 0, null, $reason);
    }

    /**
     * @param list<HanteiCheckEvidence> $checks
     *
     * @return list<string>
     */
    private function uncertainties(array $checks): array
    {
        /** @var list<string> $uncertainties */
        $uncertainties = [];
        foreach ($checks as $check) {
            if (null !== $check->reason() && !in_array($check->reason(), $uncertainties, true)) {
                $uncertainties[] = $check->reason();
            }
        }

        return $uncertainties;
    }

    private function blockedReport(
        string $reason,
        ?string $profileSha256 = null,
        ?string $subjectSha256 = null,
        ?string $artifactSha256 = null,
        ?string $sourceSetSha256 = null,
        ?string $composerSha256 = null,
        ?string $gitVersion = null,
    ): HanteiReport {
        $checks = [];
        foreach (HanteiCheckEvidence::CHECKS as $checkId => $script) {
            $checks[] = $this->blockedEvidence($checkId, $script, $reason);
        }

        return new HanteiReport(
            HanteiVerdict::Blocked,
            $profileSha256,
            $subjectSha256,
            $artifactSha256,
            $sourceSetSha256,
            PHP_VERSION,
            $composerSha256,
            $gitVersion,
            $checks,
            [$reason],
        );
    }

    private function absolutePath(string $path): bool
    {
        return str_starts_with($path, '/') || 1 === preg_match('/\A[A-Za-z]:[\\\\\/]/D', $path) || str_starts_with($path, '\\\\');
    }
}
