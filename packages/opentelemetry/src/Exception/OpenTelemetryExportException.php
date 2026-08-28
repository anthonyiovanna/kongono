<?php

declare(strict_types=1);

namespace Kongono\OpenTelemetry\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\OpenTelemetry\Diagnostic\OpenTelemetryDiagnostic;
use Throwable;

/** Preserves exact transport causes, including bounded cleanup failure composition. */
#[Api(ApiStatus::Internal)]
final class OpenTelemetryExportException extends OpenTelemetryException
{
    /** @param non-empty-list<Throwable> $failures */
    private function __construct(private readonly array $failures, ?string $signalKind, int $queueCount, int $queueCapacity, string $stage)
    {
        parent::__construct(OpenTelemetryDiagnostic::Export, $failures[0], $signalKind, $queueCount, $queueCapacity, $stage);
    }

    public static function failed(Throwable $previous, ?string $signalKind, int $queueCount, int $queueCapacity, string $stage): self
    {
        return new self([$previous], $signalKind, $queueCount, $queueCapacity, $stage);
    }

    /** @param non-empty-list<Throwable> $failures */
    public static function combined(array $failures, int $queueCount, int $queueCapacity): self
    {
        return new self($failures, null, $queueCount, $queueCapacity, 'close');
    }

    /** @return non-empty-list<Throwable> */
    public function failures(): array
    {
        return $this->failures;
    }
}
