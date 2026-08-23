<?php

declare(strict_types=1);

namespace Kongono\Hantei;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Hantei\Exception\InvalidHanteiValueException;

/** Stores bounded non-disclosing evidence for one fixed Composer check. */
#[Api(ApiStatus::Experimental)]
final readonly class HanteiCheckEvidence
{
    public const MAX_STREAM_BYTES = 8_388_608;

    /** @var array<string, string> */
    public const CHECKS = [
        'composer-schema' => 'validate:composer',
        'package-schemas' => 'validate:packages',
        'locked-advisories' => 'audit:locked',
        'platform' => 'platform',
        'knowledge-current' => 'knowledge:check',
        'style' => 'cs',
        'static-analysis' => 'analyse',
        'tests' => 'test',
    ];

    public const REASONS = [
        'profile-mismatch',
        'subject-unavailable',
        'subject-changed',
        'knowledge-unavailable',
        'tool-unavailable',
        'process-start-failed',
        'process-timeout',
        'output-limit',
        'audit-unavailable',
        'invalid-evidence',
    ];

    public function __construct(
        private string $checkId,
        private string $composerScript,
        private HanteiCheckOutcome $outcome,
        private ?int $exitCode,
        private int $stdoutBytes,
        private ?string $stdoutSha256,
        private int $stderrBytes,
        private ?string $stderrSha256,
        private ?string $reason,
    ) {
        $known = isset(self::CHECKS[$checkId]) && self::CHECKS[$checkId] === $composerScript;
        $validCounts = $stdoutBytes >= 0 && $stdoutBytes <= self::MAX_STREAM_BYTES
            && $stderrBytes >= 0 && $stderrBytes <= self::MAX_STREAM_BYTES;
        $validDigests = $this->validDigest($stdoutSha256) && $this->validDigest($stderrSha256);
        $validState = match ($outcome) {
            HanteiCheckOutcome::Pass => 0 === $exitCode && null === $reason && null !== $stdoutSha256 && null !== $stderrSha256,
            HanteiCheckOutcome::Fail => null !== $exitCode && 0 !== $exitCode && null === $reason && null !== $stdoutSha256 && null !== $stderrSha256,
            HanteiCheckOutcome::Blocked => null !== $reason && in_array($reason, self::REASONS, true)
                && ((null === $exitCode && null === $stdoutSha256 && null === $stderrSha256 && 0 === $stdoutBytes && 0 === $stderrBytes)
                    || (null !== $exitCode && null !== $stdoutSha256 && null !== $stderrSha256)),
        };
        if (!$known || !$validCounts || !$validDigests || !$validState) {
            throw InvalidHanteiValueException::evidence();
        }
    }

    public function checkId(): string
    {
        return $this->checkId;
    }

    public function composerScript(): string
    {
        return $this->composerScript;
    }

    public function outcome(): HanteiCheckOutcome
    {
        return $this->outcome;
    }

    public function exitCode(): ?int
    {
        return $this->exitCode;
    }

    public function reason(): ?string
    {
        return $this->reason;
    }

    /** @return array<string, int|string|null> */
    public function toArray(): array
    {
        return [
            'check_id' => $this->checkId,
            'composer_script' => $this->composerScript,
            'outcome' => $this->outcome->value,
            'exit_code' => $this->exitCode,
            'stdout_bytes' => $this->stdoutBytes,
            'stdout_sha256' => $this->stdoutSha256,
            'stderr_bytes' => $this->stderrBytes,
            'stderr_sha256' => $this->stderrSha256,
            'reason' => $this->reason,
        ];
    }

    private function validDigest(?string $value): bool
    {
        return null === $value || 1 === preg_match('/\A[a-f0-9]{64}\z/D', $value);
    }
}
