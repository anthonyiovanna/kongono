<?php

declare(strict_types=1);

namespace Kongono\PhpCgi\Tests\Fixture;

use Kongono\PhpCgi\Internal\CgiEmissionTarget;
use Throwable;

final class RecordingEmissionTarget implements CgiEmissionTarget
{
    /** @var list<bool> */
    private array $headersSentResults;

    /** @var list<array<string, int|string|bool>> */
    private array $calls = [];

    /** @var list<string> */
    private array $bodyChunks = [];

    /** @var list<int|false> */
    private array $writeResults;

    private int $emissionCall = 0;

    /**
     * @param list<bool> $headersSentResults
     * @param list<int|false> $writeResults
     */
    public function __construct(
        array $headersSentResults = [],
        private readonly ?int $failAtEmissionCall = null,
        private readonly ?Throwable $failure = null,
        array $writeResults = [],
    ) {
        $this->headersSentResults = $headersSentResults;
        $this->writeResults = $writeResults;
    }

    public function headersSent(): bool
    {
        $this->calls[] = ['operation' => 'headers_sent'];

        return [] === $this->headersSentResults ? false : array_shift($this->headersSentResults);
    }

    public function setStatus(int $status): void
    {
        $this->recordEmission(['operation' => 'status', 'status' => $status]);
    }

    public function emitHeader(string $name, string $value, bool $replace): void
    {
        $this->recordEmission([
            'operation' => 'header',
            'name' => $name,
            'value' => $value,
            'replace' => $replace,
        ]);
    }

    public function writeBody(string $bytes): int|false
    {
        $this->recordEmission(['operation' => 'body', 'bytes' => $bytes]);
        $this->bodyChunks[] = $bytes;

        return [] === $this->writeResults ? strlen($bytes) : array_shift($this->writeResults);
    }

    /** @return list<array<string, int|string|bool>> */
    public function calls(): array
    {
        return $this->calls;
    }

    /** @return list<array<string, int|string|bool>> */
    public function emissionCalls(): array
    {
        return array_values(array_filter(
            $this->calls,
            static fn(array $call): bool => 'headers_sent' !== $call['operation'],
        ));
    }

    /** @return list<string> */
    public function bodyChunks(): array
    {
        return $this->bodyChunks;
    }

    /** @param array<string, int|string|bool> $call */
    private function recordEmission(array $call): void
    {
        ++$this->emissionCall;
        $this->calls[] = $call;
        if ($this->emissionCall === $this->failAtEmissionCall) {
            throw $this->failure ?? new \RuntimeException('recording-target-failure');
        }
    }
}
