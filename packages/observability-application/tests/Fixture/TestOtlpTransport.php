<?php

declare(strict_types=1);

namespace Kongono\ObservabilityApplication\Tests\Fixture;

use Kongono\OpenTelemetry\OtlpHttpTransport;
use Kongono\OpenTelemetry\OtlpSignal;
use Throwable;

final class TestOtlpTransport implements OtlpHttpTransport
{
    /** @var list<OtlpSignal> */
    public array $signals = [];

    public int $flushes = 0;
    public int $closes = 0;
    public ?Throwable $sendFailure = null;
    public ?Throwable $flushFailure = null;
    public ?Throwable $closeFailure = null;

    public function send(OtlpSignal $signal, string $endpointSuffix, string $contentType, string $payload, int $timeoutMilliseconds): void
    {
        unset($endpointSuffix, $contentType, $payload, $timeoutMilliseconds);
        if (null !== $this->sendFailure) {
            throw $this->sendFailure;
        }
        $this->signals[] = $signal;
    }

    public function flush(int $timeoutMilliseconds): void
    {
        unset($timeoutMilliseconds);
        ++$this->flushes;
        if (null !== $this->flushFailure) {
            throw $this->flushFailure;
        }
    }

    public function close(int $timeoutMilliseconds): void
    {
        unset($timeoutMilliseconds);
        ++$this->closes;
        if (null !== $this->closeFailure) {
            throw $this->closeFailure;
        }
    }
}
