<?php

declare(strict_types=1);

namespace Kongono\Ai\Transport;

use Closure;
use Kongono\Ai\AiException;
use Kongono\Core\Diagnostics\SecretScrubber;

/** Pure PHP 8.5 ext-curl HTTP transport with HTTPS-by-default, SSRF egress protection, bounded buffers, and nondisclosing errors. */
final readonly class CurlTransport implements StreamingHttpTransportInterface
{
    private const int MAX_RESPONSE_HEADERS = 256;

    public function __construct(
        private bool $allowPlainHttp = false,
        private bool $allowPrivateNetworks = false,
        private int $maxResponseBytes = 10_485_760,
        private int $maxStreamBytes = 16_777_216,
    ) {}

    public function postJson(string $url, array $headers, string $jsonBody, int $timeoutSeconds = 60): HttpResponse
    {
        $target = $this->admitUrl($url);
        $ch = curl_init($target['url']);
        if (false === $ch) {
            throw new AiException('Unable to initialize cURL handle.');
        }

        $headerLines = ['Content-Type: application/json', 'Accept: application/json', ...self::formatHeaders($headers)];

        $responseHeaders = [];
        $body = '';
        $overflowed = false;
        $maxBytes = $this->maxResponseBytes;

        $options = [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $jsonBody,
            CURLOPT_HTTPHEADER => $headerLines,
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_PROTOCOLS_STR => $target['scheme'],
            CURLOPT_REDIR_PROTOCOLS_STR => $target['scheme'],
            CURLOPT_CONNECTTIMEOUT => min(15, max(1, $timeoutSeconds)),
            CURLOPT_TIMEOUT => max(1, $timeoutSeconds),
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HEADERFUNCTION => static function (mixed $_ch, string $line) use (&$responseHeaders): int {
                $parts = explode(':', $line, 2);
                if (2 === count($parts) && count($responseHeaders) < self::MAX_RESPONSE_HEADERS) {
                    $responseHeaders[strtolower(trim($parts[0]))] = trim($parts[1]);
                }

                return strlen($line);
            },
            CURLOPT_WRITEFUNCTION => static function (mixed $_ch, string $chunk) use (&$body, &$overflowed, $maxBytes): int {
                if (strlen($body) + strlen($chunk) > $maxBytes) {
                    $overflowed = true;

                    return 0;
                }
                $body .= $chunk;

                return strlen($chunk);
            },
        ];
        if (null !== $target['pinIp']) {
            $pin = str_contains($target['pinIp'], ':') ? '[' . $target['pinIp'] . ']' : $target['pinIp'];
            $options[CURLOPT_RESOLVE] = [sprintf('%s:%d:%s', $target['host'], $target['port'], $pin)];
        }
        curl_setopt_array($ch, $options);

        $ok = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $error = curl_error($ch);

        if ($overflowed) {
            throw new AiException(sprintf('AI HTTP response exceeded maximum bound of %d bytes.', $this->maxResponseBytes));
        }
        if (false === $ok) {
            throw new AiException('AI HTTP transport failed: ' . SecretScrubber::scrub($error));
        }

        return new HttpResponse($statusCode, $body, $responseHeaders);
    }

    public function postJsonStream(string $url, array $headers, string $jsonBody, Closure $onChunk, int $timeoutSeconds = 120): int
    {
        $target = $this->admitUrl($url);
        $ch = curl_init($target['url']);
        if (false === $ch) {
            throw new AiException('Unable to initialize cURL handle.');
        }

        $headerLines = ['Content-Type: application/json', 'Accept: text/event-stream, application/x-ndjson, application/json', ...self::formatHeaders($headers)];
        $streamedBytes = 0;
        $overflowed = false;
        $maxStream = $this->maxStreamBytes;

        $options = [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $jsonBody,
            CURLOPT_HTTPHEADER => $headerLines,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_PROTOCOLS_STR => $target['scheme'],
            CURLOPT_REDIR_PROTOCOLS_STR => $target['scheme'],
            CURLOPT_CONNECTTIMEOUT => min(15, max(1, $timeoutSeconds)),
            CURLOPT_TIMEOUT => max(1, $timeoutSeconds),
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_WRITEFUNCTION => static function (mixed $_ch, string $chunk) use ($onChunk, &$streamedBytes, &$overflowed, $maxStream): int {
                $streamedBytes += strlen($chunk);
                if ($streamedBytes > $maxStream) {
                    $overflowed = true;

                    return 0;
                }
                $onChunk($chunk);

                return strlen($chunk);
            },
        ];
        if (null !== $target['pinIp']) {
            $pin = str_contains($target['pinIp'], ':') ? '[' . $target['pinIp'] . ']' : $target['pinIp'];
            $options[CURLOPT_RESOLVE] = [sprintf('%s:%d:%s', $target['host'], $target['port'], $pin)];
        }
        curl_setopt_array($ch, $options);

        $ok = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $error = curl_error($ch);

        if ($overflowed) {
            throw new AiException(sprintf('AI streaming response exceeded maximum bound of %d bytes.', $this->maxStreamBytes));
        }
        if (false === $ok) {
            throw new AiException('AI streaming transport failed: ' . SecretScrubber::scrub($error));
        }

        return $statusCode;
    }

    /** @return array{scheme: 'http'|'https', host: string, port: int, url: string, pinIp: ?string} */
    private function admitUrl(string $url): array
    {
        if ('' === $url || 1 === preg_match('/[\x00-\x1f\x7f\\\\]/', $url)) {
            throw new AiException('AI transport URL contains forbidden control characters or backslashes.');
        }

        $parts = parse_url($url);
        $scheme = is_array($parts) && is_string($parts['scheme'] ?? null) ? strtolower($parts['scheme']) : '';
        if ('https' !== $scheme && !($this->allowPlainHttp && 'http' === $scheme)) {
            throw new AiException(sprintf('Unsupported AI transport URL scheme "%s".', $scheme));
        }
        if (isset($parts['user']) || isset($parts['pass'])) {
            throw new AiException('Embedded credentials in AI transport URLs are forbidden.');
        }
        $rawHost = is_string($parts['host'] ?? null) ? $parts['host'] : '';
        if ('' === $rawHost || str_contains($rawHost, '%') || 1 === preg_match('/[^\x21-\x7e]/', $rawHost)) {
            throw new AiException('AI transport URL must contain a valid ASCII host.');
        }

        $host = strtolower($rawHost);
        $port = $parts['port'] ?? ('https' === $scheme ? 443 : 80);
        $pinIp = null;

        if (!$this->allowPrivateNetworks) {
            $trimmedHost = trim($host, '[]');
            $ips = false !== filter_var($trimmedHost, FILTER_VALIDATE_IP)
                ? [$trimmedHost]
                : $this->resolveHostIps($host);
            if ([] === $ips) {
                throw new AiException(sprintf('Unable to resolve AI transport host "%s".', $host));
            }
            foreach ($ips as $ip) {
                if (!self::isPublicIp($ip)) {
                    throw new AiException(sprintf('AI transport host "%s" resolved to a private/reserved IP address (SSRF blocked).', $host));
                }
            }
            $pinIp = $ips[0];
        }

        $defaultPort = 'https' === $scheme ? 443 : 80;
        $authority = $port === $defaultPort ? $host : $host . ':' . $port;
        $path = is_string($parts['path'] ?? null) && '' !== $parts['path'] ? $parts['path'] : '/';
        $query = is_string($parts['query'] ?? null) && '' !== $parts['query'] ? '?' . $parts['query'] : '';

        return [
            'scheme' => $scheme,
            'host' => $host,
            'port' => $port,
            'url' => sprintf('%s://%s%s%s', $scheme, $authority, $path, $query),
            'pinIp' => $pinIp,
        ];
    }

    /** @return list<string> */
    private function resolveHostIps(string $host): array
    {
        $ips = [];
        $v4 = @gethostbynamel($host);
        if (is_array($v4)) {
            array_push($ips, ...$v4);
        }
        if (function_exists('dns_get_record')) {
            $aaaa = @dns_get_record($host, DNS_AAAA);
            if (is_array($aaaa)) {
                foreach ($aaaa as $rec) {
                    if (is_string($rec['ipv6'] ?? null)) {
                        $ips[] = $rec['ipv6'];
                    }
                }
            }
        }

        return array_values(array_unique($ips));
    }

    private static function isPublicIp(string $ip): bool
    {
        $packed = @inet_pton($ip);
        if (false === $packed) {
            return false;
        }
        if (16 === strlen($packed)) {
            if (str_starts_with($packed, "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\xff\xff")) {
                $v4 = @inet_ntop(substr($packed, 12, 4));

                return is_string($v4) && self::isPublicIp($v4);
            }
            $b0 = ord($packed[0]);
            $b1 = ord($packed[1]);
            if (
                str_starts_with($packed, "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00")
                || str_starts_with($packed, "\x00\x64\xff\x9b")
                || str_starts_with($packed, "\x01\x00\x00\x00\x00\x00\x00\x00")
                || (0x20 === $b0 && 0x02 === $b1)
                || (0x20 === $b0 && 0x01 === $b1 && 0x00 === ord($packed[2]) && 0x00 === ord($packed[3]))
                || 0xfc === ($b0 & 0xfe)
                || (0xfe === $b0 && 0x80 === ($b1 & 0x80))
                || 0xff === $b0
            ) {
                return false;
            }
        } elseif (4 === strlen($packed)) {
            $o0 = ord($packed[0]);
            $o1 = ord($packed[1]);
            if (
                0 === $o0 || 10 === $o0 || 127 === $o0
                || (100 === $o0 && $o1 >= 64 && $o1 <= 127)
                || (169 === $o0 && 254 === $o1)
                || (172 === $o0 && $o1 >= 16 && $o1 <= 31)
                || (192 === $o0 && 168 === $o1)
                || (198 === $o0 && (18 === $o1 || 19 === $o1))
                || $o0 >= 224
            ) {
                return false;
            }
        }

        return false !== filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
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
