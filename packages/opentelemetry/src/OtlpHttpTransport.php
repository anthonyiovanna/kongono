<?php

declare(strict_types=1);

namespace Kongono\OpenTelemetry;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Receives fully explicit OTLP/HTTP payload arguments without owning HTTP configuration. */
#[Api(ApiStatus::Experimental)]
interface OtlpHttpTransport
{
    public function send(OtlpSignal $signal, string $endpointSuffix, string $contentType, string $payload, int $timeoutMilliseconds): void;

    public function flush(int $timeoutMilliseconds): void;

    public function close(int $timeoutMilliseconds): void;
}
