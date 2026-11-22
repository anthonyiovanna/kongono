<?php

declare(strict_types=1);

namespace Kongono\Ai\Transport;

use Closure;
use Kongono\Ai\AiException;
use Kongono\Core\Diagnostics\SecretScrubber;

/** Pure PHP 8.5 ext-curl HTTP transport with TLS verification, streaming, and nondisclosing errors. */
final class CurlTransport implements StreamingHttpTransportInterface
{
    public function postJson(string $url, array $headers, string $jsonBody, int $timeoutSeconds = 60): HttpResponse
    {
        $scheme = self::assertAllowedUrl($url);
        $ch = curl_init($url);
        if (false === $ch) {
            throw new AiException('Unable to initialize cURL handle.');
        }

        $headerLines = ['Content-Type: application/json', 'Accept: application/json', ...self::formatHeaders($headers)];

        $responseHeaders = [];
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $jsonBody,
            CURLOPT_HTTPHEADER => $headerLines,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_PROTOCOLS_STR => $scheme,
            CURLOPT_REDIR_PROTOCOLS_STR => $scheme,
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

    public function postJsonStream(string $url, array $headers, string $jsonBody, Closure $onChunk, int $timeoutSeconds = 120): int
    {
        $scheme = self::assertAllowedUrl($url);
        $ch = curl_init($url);
        if (false === $ch) {
            throw new AiException('Unable to initialize cURL handle.');
        }

        $headerLines = ['Content-Type: application/json', 'Accept: text/event-stream, application/x-ndjson, application/json', ...self::formatHeaders($headers)];

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $jsonBody,
            CURLOPT_HTTPHEADER => $headerLines,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_PROTOCOLS_STR => $scheme,
            CURLOPT_REDIR_PROTOCOLS_STR => $scheme,
            CURLOPT_CONNECTTIMEOUT => min(15, max(1, $timeoutSeconds)),
            CURLOPT_TIMEOUT => max(1, $timeoutSeconds),
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_WRITEFUNCTION => static function (mixed $_ch, string $chunk) use ($onChunk): int {
                $onChunk($chunk);

                return strlen($chunk);
            },
        ]);

        $ok = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $error = curl_error($ch);

        if (false === $ok) {
            throw new AiException('AI streaming transport failed: ' . SecretScrubber::scrub($error));
        }

        return $statusCode;
    }

    /** @return 'http'|'https' */
    private static function assertAllowedUrl(string $url): string
    {
        $parts = parse_url($url);
        $scheme = is_array($parts) && is_string($parts['scheme'] ?? null) ? strtolower($parts['scheme']) : '';
        if ('https' !== $scheme && 'http' !== $scheme) {
            throw new AiException(sprintf('Unsupported AI transport URL scheme "%s".', $scheme));
        }
        if (!is_string($parts['host'] ?? null) || '' === $parts['host']) {
            throw new AiException('AI transport URL must contain a valid host.');
        }

        return $scheme;
    }

    /**
     * @param array<string, string> $headers
     * @return list<string>
     */
    private static function formatHeaders(array $headers): array
    {
        $lines = [];
        foreach ($headers as $name => $value) {
            if (1 !== preg_match('/\A[a-zA-Z0-9!#$%&\'*+.^_`|~-]+\z/', $name) || false !== strpbrk($value, "\r\n\0")) {
                throw new AiException(sprintf('Unsafe AI request header "%s" rejected.', $name));
            }
            $lines[] = $name . ': ' . $value;
        }

        return $lines;
    }
}
