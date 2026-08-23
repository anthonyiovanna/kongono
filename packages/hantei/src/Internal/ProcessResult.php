<?php

declare(strict_types=1);

namespace Kongono\Hantei\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Hantei\Exception\InvalidHanteiValueException;

/** Retains bounded process bytes only until Hantei derives non-disclosing evidence. */
#[Api(ApiStatus::Internal)]
final readonly class ProcessResult
{
    public function __construct(
        private bool $started,
        private ?int $exitCode,
        private string $stdout,
        private string $stderr,
        private ?string $blockedReason,
    ) {
        $valid = $started
            ? null !== $exitCode && (null === $blockedReason || in_array($blockedReason, ['process-timeout', 'output-limit', 'invalid-evidence'], true))
            : null === $exitCode && '' === $stdout && '' === $stderr && 'process-start-failed' === $blockedReason;
        if (!$valid) {
            throw InvalidHanteiValueException::process();
        }
    }

    public function started(): bool
    {
        return $this->started;
    }

    public function exitCode(): ?int
    {
        return $this->exitCode;
    }

    public function stdout(): string
    {
        return $this->stdout;
    }

    public function stderr(): string
    {
        return $this->stderr;
    }

    public function blockedReason(): ?string
    {
        return $this->blockedReason;
    }
}
