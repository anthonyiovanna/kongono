<?php

declare(strict_types=1);

namespace Kongono\Ai\Transport;

interface HttpTransportInterface
{
    /**
     * Send an HTTP POST request with a JSON string body.
     *
     * @param array<string, string> $headers
     */
    public function postJson(string $url, array $headers, string $jsonBody, int $timeoutSeconds = 60): HttpResponse;
}
