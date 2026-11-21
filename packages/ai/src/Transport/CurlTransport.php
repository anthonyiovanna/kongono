<?php

declare(strict_types=1);

namespace Kongono\Ai\Transport;

use Kongono\Ai\AiException;
use Kongono\Core\Diagnostics\SecretScrubber;

/** Pure PHP 8.5 ext-curl HTTP transport with TLS verification and nondisclosing errors. */
final class CurlTransport implements HttpTransportInterface
{
    public function postJson(string $url, array $headers, string $jsonBody, int $timeoutSeconds = 60): HttpResponse
    {
        $ch = curl_init($url);
        if (false === $ch) {
            throw new AiException('Unable to initialize cURL handle.');
        }

        $headerLines = ['Content-Type: application/json', 'Accept: application/json'];
        foreach ($headers as $name => $value) {
            $headerLines[] = $name . ': ' . $value;
        }

        $responseHeaders = [];
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $jsonBody,
            CURLOPT_HTTPHEADER => $headerLines,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => min(15, max(1, $timeoutSeconds)),
            CURLOPT_TIMEOUT => max(1, $timeoutSeconds),
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HEADERFUNCTION => static function (mixed $_ch, string $line) use (&$responseHeaders): int {
                $parts = explode(':', $line, 2);
                if (2 === count($parts)) {
                    $responseHeaders[strtolower(trim($parts[0]))] = trim($parts[1]);
                }

                return strlen($line);
            },
        ]);

        $raw = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $error = curl_error($ch);

        if (!is_string($raw)) {
            throw new AiException('AI HTTP transport failed: ' . SecretScrubber::scrub($error));
        }

        return new HttpResponse($statusCode, $raw, $responseHeaders);
    }
}
