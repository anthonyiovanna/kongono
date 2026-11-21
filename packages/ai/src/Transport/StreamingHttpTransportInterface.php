<?php

declare(strict_types=1);

namespace Kongono\Ai\Transport;

use Closure;

interface StreamingHttpTransportInterface extends HttpTransportInterface
{
    /**
     * Stream an HTTP POST JSON response chunk-by-chunk as bytes arrive.
     *
     * @param array<string, string> $headers
     * @param Closure(string): void $onChunk
     */
    public function postJsonStream(string $url, array $headers, string $jsonBody, Closure $onChunk, int $timeoutSeconds = 120): int;
}
