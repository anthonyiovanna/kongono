<?php

declare(strict_types=1);

namespace Kongono\AssetAdapterContract\Process;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetAdapterContract\Exception\InvalidAssetAdapterContractException;

/** Carries complete bounded direct-process lifecycle evidence. */
#[Api(ApiStatus::Experimental)]
final readonly class BuildProcessResult
{
    private const array REASONS = ['none','start_failed','timeout','stdout_limit','stderr_limit','read_failed','terminate_failed','stream_close_failed','process_close_failed'];
    public function __construct(private bool $started, private ?int $exitCode, private string $stdout, private string $stderr, private bool $timedOut, private bool $stdoutLimitExceeded, private bool $stderrLimitExceeded, private bool $terminated, private bool $streamsClosed, private bool $processClosed, private string $failureReason)
    {
        if (!in_array($failureReason, self::REASONS, true) || (!$started && null !== $exitCode) || ($started && !$processClosed && null !== $exitCode) || ('none' === $failureReason && (!$started || !$streamsClosed || !$processClosed || $timedOut || $stdoutLimitExceeded || $stderrLimitExceeded))) {
            throw InvalidAssetAdapterContractException::process('result_relation');
        }
    }
    public function started(): bool
    {
        return $this->started;
    } public function exitCode(): ?int
    {
        return $this->exitCode;
    } public function stdout(): string
    {
        return $this->stdout;
    } public function stderr(): string
    {
        return $this->stderr;
    }
    public function stdoutByteCount(): int
    {
        return strlen($this->stdout);
    } public function stderrByteCount(): int
    {
        return strlen($this->stderr);
    } public function stdoutSha256(): string
    {
        return hash('sha256', $this->stdout);
    } public function stderrSha256(): string
    {
        return hash('sha256', $this->stderr);
    }
    public function timedOut(): bool
    {
        return $this->timedOut;
    } public function stdoutLimitExceeded(): bool
    {
        return $this->stdoutLimitExceeded;
    } public function stderrLimitExceeded(): bool
    {
        return $this->stderrLimitExceeded;
    } public function terminated(): bool
    {
        return $this->terminated;
    } public function streamsClosed(): bool
    {
        return $this->streamsClosed;
    } public function processClosed(): bool
    {
        return $this->processClosed;
    } public function failureReason(): string
    {
        return $this->failureReason;
    }
    public function succeeded(): bool
    {
        return $this->started && 0 === $this->exitCode && 'none' === $this->failureReason;
    }
}
