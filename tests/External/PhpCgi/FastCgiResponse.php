<?php

declare(strict_types=1);

namespace Kongono\Tests\External\PhpCgi;

use RuntimeException;

/** Immutable bounded interpretation of one complete CGI response. */
final readonly class FastCgiResponse
{
    private const int MAX_CGI_HEADER_BYTES = 131_072;

    /**
     * @param array<string, list<string>> $fields
     */
    private function __construct(
        public int $statusCode,
        public array $fields,
        public string $body,
        public string $stderr,
        public int $applicationStatus,
        public int $protocolStatus,
    ) {}

    public static function fromDecoded(
        string $stdout,
        string $stderr,
        int $applicationStatus,
        int $protocolStatus,
    ): self {
        if (0 !== $applicationStatus || 0 !== $protocolStatus) {
            throw new RuntimeException('fastcgi_end_status_nonzero');
        }
        if (strlen($stdout) > 1_200_000 || strlen($stderr) > 65_536) {
            throw new RuntimeException('fastcgi_response_limit_exceeded');
        }
        $separator = strpos($stdout, "\r\n\r\n");
        if (false === $separator) {
            throw new RuntimeException('cgi_header_separator_missing');
        }
        $headerBlock = substr($stdout, 0, $separator);
        $body = substr($stdout, $separator + 4);
        if (strlen($headerBlock) > self::MAX_CGI_HEADER_BYTES || strlen($body) > 1_048_576) {
            throw new RuntimeException('cgi_response_limit_exceeded');
        }
        if (str_contains(str_replace("\r\n", '', $headerBlock), "\r") || str_contains(str_replace("\r\n", '', $headerBlock), "\n")) {
            throw new RuntimeException('cgi_header_line_ending_invalid');
        }

        $status = 200;
        $statusSeen = false;
        /** @var array<string, list<string>> $fields */
        $fields = [];
        /** @var array<string, string> $canonicalNames */
        $canonicalNames = [];
        foreach ('' === $headerBlock ? [] : explode("\r\n", $headerBlock) as $line) {
            $colon = strpos($line, ':');
            if (false === $colon) {
                throw new RuntimeException('cgi_header_line_invalid');
            }
            $name = substr($line, 0, $colon);
            $value = ltrim(substr($line, $colon + 1), " \t");
            if (1 !== preg_match("/^[!#$%&'*+\\-.^_`|~0-9A-Za-z]+$/D", $name)) {
                throw new RuntimeException('cgi_header_name_invalid');
            }
            if (1 === preg_match('/[\x00-\x08\x0A-\x1F\x7F]/', $value)) {
                throw new RuntimeException('cgi_header_value_invalid');
            }
            $normalized = strtolower($name);
            if ('status' === $normalized) {
                if ($statusSeen || 1 !== preg_match('/^[2-5][0-9]{2}(?: [\x20-\x7E]*)?$/D', $value)) {
                    throw new RuntimeException('cgi_status_invalid');
                }
                $status = (int) substr($value, 0, 3);
                $statusSeen = true;
                continue;
            }
            $canonical = $canonicalNames[$normalized] ?? $name;
            $canonicalNames[$normalized] = $canonical;
            $fields[$canonical] ??= [];
            $fields[$canonical][] = $value;
        }

        return new self($status, $fields, $body, $stderr, $applicationStatus, $protocolStatus);
    }
}
