<?php

declare(strict_types=1);

namespace KongonoObservabilityMinimal;

use Kongono\OpenTelemetry\OtlpHttpTransport;
use Kongono\OpenTelemetry\OtlpSignal;
use RuntimeException;

/** Captures a small bounded set of OTLP payloads in memory and performs no network access. */
final class CapturingOtlpTransport implements OtlpHttpTransport
{
    private const int MAX_PAYLOADS = 8;
    private const int MAX_PAYLOAD_BYTES = 65_536;

    /** @var list<string> */
    private array $payloads = [];

    /** @var list<OtlpSignal> */
    private array $signals = [];

    private int $flushCount = 0;

    private int $closeCount = 0;

    public function send(OtlpSignal $signal, string $endpointSuffix, string $contentType, string $payload, int $timeoutMilliseconds): void
    {
        unset($endpointSuffix, $contentType, $timeoutMilliseconds);
        if (count($this->payloads) >= self::MAX_PAYLOADS || strlen($payload) > self::MAX_PAYLOAD_BYTES) {
            throw new RuntimeException('The example capture bound was exceeded.');
        }

        $this->signals[] = $signal;
        $this->payloads[] = $payload;
    }

    public function flush(int $timeoutMilliseconds): void
    {
        unset($timeoutMilliseconds);
        ++$this->flushCount;
    }

    public function close(int $timeoutMilliseconds): void
    {
        unset($timeoutMilliseconds);
        ++$this->closeCount;
        foreach ($this->payloads as $index => $_payload) {
            $this->payloads[$index] = '';
        }
        $this->payloads = [];
    }

    public function payloadCount(): int
    {
        return count($this->payloads);
    }

    /** @return list<OtlpSignal> */
    public function signals(): array
    {
        return $this->signals;
    }

    public function flushCount(): int
    {
        return $this->flushCount;
    }

    public function closeCount(): int
    {
        return $this->closeCount;
    }
}
